import type { InputHTMLAttributes } from 'react';
import styles from './Input.module.css';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  label: string;
  error?: string;
};

export function Input({ label, error, id, className = '', ...rest }: Props) {
  const inputId = id ?? `input-${label.replace(/\s+/g, '-').toLowerCase()}`;

  return (
    <label className={`${styles.field} ${className}`.trim()} htmlFor={inputId}>
      <span className={styles.label}>{label}</span>
      <input id={inputId} className={styles.input} aria-invalid={Boolean(error)} {...rest} />
      {error ? <span className={styles.error}>{error}</span> : null}
    </label>
  );
}
