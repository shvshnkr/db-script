#!/bin/bash
# Dbscript service control — probe/resolve/execute for systemd/SysV on Debian/Ubuntu.
# Testable via DBS_OS_RELEASE_FILE and mock bin in PATH (tests/servicectl/mock-bin).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VALID_ACTIONS="db.stop db.start db.restart web.reload web.restart php-fpm.reload"
PROBE_CACHE="${DBS_PROBE_CACHE:-$ROOT/_local/servicectl.probe.json}"
PROBE_TTL="${DBS_PROBE_TTL:-60}"

usage() {
    echo "Usage: $0 --probe | --resolve <action> | --execute <action> [--dry-run]" >&2
    exit 2
}

json_escape() {
    local s="$1"
    s="${s//\\/\\\\}"
    s="${s//\"/\\\"}"
    s="${s//$'\n'/\\n}"
    s="${s//$'\r'/}"
    printf '%s' "$s"
}

read_os_release() {
    local f="${DBS_OS_RELEASE_FILE:-/etc/os-release}"
    OS_ID="unknown"
    OS_VERSION_ID=""
    OS_ID_LIKE=""
    if [[ -f "$f" ]]; then
        # shellcheck disable=SC1090
        . "$f"
        OS_ID="${ID:-unknown}"
        OS_VERSION_ID="${VERSION_ID:-}"
        OS_ID_LIKE="${ID_LIKE:-}"
    fi
}

have_cmd() {
    command -v "$1" >/dev/null 2>&1
}

privilege_mode() {
    if [[ "${DBS_PRIVILEGE:-}" != "" ]]; then
        echo "$DBS_PRIVILEGE"
        return
    fi
    if [[ "$(id -u)" -eq 0 ]]; then
        echo "root"
        return
    fi
    if have_cmd sudo && sudo -n true 2>/dev/null; then
        echo "sudo_nopasswd"
        return
    fi
    echo "none"
}

unit_active_or_enabled() {
    local unit="$1"
    if have_cmd systemctl; then
        systemctl is-active --quiet "$unit" 2>/dev/null && return 0
        systemctl is-enabled --quiet "$unit" 2>/dev/null && return 0
    fi
    return 1
}

pick_db_unit() {
    local u
    if [[ -n "${DBS_DB_UNIT:-}" ]]; then
        echo "$DBS_DB_UNIT"
        return
    fi
    for u in mariadb mysql mysqld; do
        if unit_active_or_enabled "$u"; then
            echo "$u"
            return
        fi
    done
    echo ""
}

pick_web_units() {
    local units=()
    local u
    if [[ -n "${DBS_WEB_UNITS:-}" ]]; then
        echo "$DBS_WEB_UNITS"
        return
    fi
    for u in apache2 nginx; do
        if unit_active_or_enabled "$u"; then
            units+=("$u")
        fi
    done
    if have_cmd systemctl; then
        while IFS= read -r u; do
            [[ -n "$u" ]] && units+=("$u")
        done < <(systemctl list-units --type=service --all --no-legend 'php*-fpm.service' 2>/dev/null | awk '{print $1}')
    fi
    local IFS=,
    echo "${units[*]}"
}

init_type() {
    if have_cmd systemctl && [[ -d /run/systemd/system || -d /usr/lib/systemd/system ]]; then
        echo "systemd"
        return
    fi
    if have_cmd service; then
        echo "sysv"
        return
    fi
    echo "none"
}

web_stack_label() {
    local units="$1"
    if [[ -z "$units" ]]; then
        echo "none"
        return
    fi
    echo "$units" | tr ',' '+'
}

build_actions_available() {
    local priv="$1"
    local db_unit="$2"
    local web_units="$3"
    local actions=()
    if [[ "$priv" == "none" ]]; then
        echo ""
        return
    fi
    if [[ -n "$db_unit" ]]; then
        actions+=("db.stop" "db.start" "db.restart")
    fi
    if [[ -n "$web_units" ]]; then
        actions+=("web.reload" "web.restart")
        if [[ "$web_units" == *"fpm"* ]]; then
            actions+=("php-fpm.reload")
        fi
    fi
    local IFS=,
    echo "${actions[*]}"
}

run_systemctl() {
    local cmd="$1"
    shift
    local priv
    priv="$(privilege_mode)"
    if [[ "$priv" == "root" ]]; then
        systemctl "$cmd" "$@"
    elif [[ "$priv" == "sudo_nopasswd" ]]; then
        sudo -n systemctl "$cmd" "$@"
    else
        return 1
    fi
}

run_service() {
    local name="$1"
    local cmd="$2"
    local priv
    priv="$(privilege_mode)"
    if [[ "$priv" == "root" ]]; then
        service "$name" "$cmd"
    elif [[ "$priv" == "sudo_nopasswd" ]]; then
        sudo -n service "$name" "$cmd"
    else
        return 1
    fi
}

resolve_action() {
    local action="$1"
    local init db_unit web_units
    init="$(init_type)"
    db_unit="$(pick_db_unit)"
    web_units="$(pick_web_units)"

    case "$action" in
        db.stop)
            [[ -n "$db_unit" ]] || return 1
            if [[ "$init" == "systemd" ]]; then echo "systemctl stop $db_unit"; return 0; fi
            if [[ "$init" == "sysv" ]]; then echo "service $db_unit stop"; return 0; fi
            ;;
        db.start)
            [[ -n "$db_unit" ]] || return 1
            if [[ "$init" == "systemd" ]]; then echo "systemctl start $db_unit"; return 0; fi
            if [[ "$init" == "sysv" ]]; then echo "service $db_unit start"; return 0; fi
            ;;
        db.restart)
            [[ -n "$db_unit" ]] || return 1
            if [[ "$init" == "systemd" ]]; then echo "systemctl restart $db_unit"; return 0; fi
            if [[ "$init" == "sysv" ]]; then echo "service $db_unit restart"; return 0; fi
            ;;
        web.reload|web.restart)
            [[ -n "$web_units" ]] || return 1
            local cmd="reload"
            [[ "$action" == "web.restart" ]] && cmd="restart"
            local u first=1 line=""
            IFS=',' read -ra arr <<< "$web_units"
            for u in "${arr[@]}"; do
                [[ -z "$u" ]] && continue
                if [[ "$init" == "systemd" ]]; then
                    [[ $first -eq 0 ]] && line+=" && "
                    line+="systemctl $cmd $u"
                elif [[ "$init" == "sysv" ]]; then
                    [[ $first -eq 0 ]] && line+=" && "
                    line+="service $u $cmd"
                fi
                first=0
            done
            [[ -n "$line" ]] && echo "$line" && return 0
            ;;
        php-fpm.reload)
            [[ -n "$web_units" ]] || return 1
            local u line="" first=1
            IFS=',' read -ra arr <<< "$web_units"
            for u in "${arr[@]}"; do
                [[ "$u" != *fpm* ]] && continue
                [[ $first -eq 0 ]] && line+=" && "
                line+="systemctl reload $u"
                first=0
            done
            [[ -n "$line" ]] && echo "$line" && return 0
            ;;
    esac
    return 1
}

execute_action() {
    local action="$1"
    local init db_unit web_units priv
    init="$(init_type)"
    db_unit="$(pick_db_unit)"
    web_units="$(pick_web_units)"
    priv="$(privilege_mode)"
    [[ "$priv" != "none" ]] || return 1

    case "$action" in
        db.stop)
            [[ -n "$db_unit" ]] || return 1
            if [[ "$init" == "systemd" ]]; then run_systemctl stop "$db_unit"; return $?; fi
            if [[ "$init" == "sysv" ]]; then run_service "$db_unit" stop; return $?; fi
            ;;
        db.start)
            [[ -n "$db_unit" ]] || return 1
            if [[ "$init" == "systemd" ]]; then run_systemctl start "$db_unit"; return $?; fi
            if [[ "$init" == "sysv" ]]; then run_service "$db_unit" start; return $?; fi
            ;;
        db.restart)
            [[ -n "$db_unit" ]] || return 1
            if [[ "$init" == "systemd" ]]; then run_systemctl restart "$db_unit"; return $?; fi
            if [[ "$init" == "sysv" ]]; then run_service "$db_unit" restart; return $?; fi
            ;;
        web.reload)
            [[ -n "$web_units" ]] || return 1
            local u
            IFS=',' read -ra arr <<< "$web_units"
            for u in "${arr[@]}"; do
                [[ -z "$u" ]] && continue
                if [[ "$init" == "systemd" ]]; then run_systemctl reload "$u" || return $?; fi
                if [[ "$init" == "sysv" ]]; then run_service "$u" reload || return $?; fi
            done
            return 0
            ;;
        web.restart)
            [[ -n "$web_units" ]] || return 1
            local u
            IFS=',' read -ra arr <<< "$web_units"
            for u in "${arr[@]}"; do
                [[ -z "$u" ]] && continue
                if [[ "$init" == "systemd" ]]; then run_systemctl restart "$u" || return $?; fi
                if [[ "$init" == "sysv" ]]; then run_service "$u" restart || return $?; fi
            done
            return 0
            ;;
        php-fpm.reload)
            local u
            IFS=',' read -ra arr <<< "$web_units"
            for u in "${arr[@]}"; do
                [[ "$u" != *fpm* ]] && continue
                run_systemctl reload "$u" || return $?
            done
            return 0
            ;;
    esac
    return 1
}

probe_json() {
    read_os_release
    local init priv db_unit web_units actions stack
    init="$(init_type)"
    priv="$(privilege_mode)"
    db_unit="$(pick_db_unit)"
    web_units="$(pick_web_units)"
    stack="$(web_stack_label "$web_units")"
    actions="$(build_actions_available "$priv" "$db_unit" "$web_units")"

    printf '{'
    printf '"os_id":"%s",' "$(json_escape "$OS_ID")"
    printf '"version":"%s",' "$(json_escape "$OS_VERSION_ID")"
    printf '"id_like":"%s",' "$(json_escape "$OS_ID_LIKE")"
    printf '"init":"%s",' "$(json_escape "$init")"
    printf '"web_stack":"%s",' "$(json_escape "$stack")"
    printf '"db_unit":"%s",' "$(json_escape "$db_unit")"
    printf '"web_units":"%s",' "$(json_escape "$web_units")"
    printf '"privilege":"%s",' "$(json_escape "$priv")"
    printf '"actions_available":"%s"' "$(json_escape "$actions")"
    printf '}\n'
}

validate_action() {
    local action="$1"
    case " $VALID_ACTIONS " in
        *" $action "*) return 0 ;;
    esac
    return 1
}

[[ $# -lt 1 ]] && usage

MODE="$1"
shift || true

case "$MODE" in
    --probe)
        if [[ -f "$PROBE_CACHE" ]]; then
            now=$(date +%s)
            mtime=$(stat -c %Y "$PROBE_CACHE" 2>/dev/null || stat -f %m "$PROBE_CACHE" 2>/dev/null || echo 0)
            if (( now - mtime < PROBE_TTL )); then
                cat "$PROBE_CACHE"
                exit 0
            fi
        fi
        probe_json | tee "$PROBE_CACHE" 2>/dev/null || probe_json
        ;;
    --resolve)
        [[ $# -ge 1 ]] || usage
        validate_action "$1" || { echo "unknown action: $1" >&2; exit 1; }
        resolve_action "$1" || exit 1
        ;;
    --execute|--dry-run)
        [[ $# -ge 1 ]] || usage
        validate_action "$1" || { echo "unknown action: $1" >&2; exit 1; }
        if [[ "$MODE" == "--dry-run" ]]; then
            resolve_action "$1" || exit 1
            exit 0
        fi
        execute_action "$1"
        ;;
    *)
        usage
        ;;
esac
