import { useEffect, useState, type FormEvent } from 'react';
import type { ColumnMeta } from '../../api/editor';
import { Button } from '../ui/Button';
import { Input } from '../ui/Input';
import styles from './RecordForm.module.css';

type Props = {
  columns: ColumnMeta[];
  initial?: Record<string, unknown>;
  onSubmit: (data: Record<string, unknown>) => Promise<void>;
  onCancel: () => void;
  title: string;
};

export function RecordForm({ columns, initial, onSubmit, onCancel, title }: Props) {
  const [values, setValues] = useState<Record<string, string>>({});
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    const next: Record<string, string> = {};
    for (const column of columns) {
      if (initial && column.name in initial) {
        next[column.name] = String(initial[column.name] ?? '');
      } else {
        next[column.name] = '';
      }
    }
    setValues(next);
    setErrors({});
  }, [columns, initial]);

  const onFieldChange = (name: string, value: string) => {
    setValues((prev) => ({ ...prev, [name]: value }));
    setErrors((prev) => {
      const copy = { ...prev };
      delete copy[name];
      return copy;
    });
  };

  const validate = (): boolean => {
    const next: Record<string, string> = {};
    for (const column of columns) {
      if (column.primary && initial) {
        continue;
      }
      if (!column.nullable && values[column.name]?.trim() === '') {
        next[column.name] = 'Required';
      }
    }
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!validate()) {
      return;
    }

    setSubmitting(true);
    try {
      const payload: Record<string, unknown> = {};
      for (const column of columns) {
        if (column.primary && initial) {
          continue;
        }
        payload[column.name] = values[column.name];
      }
      await onSubmit(payload);
    } finally {
      setSubmitting(false);
    }
  };

  const editableColumns = columns.filter((column) => !(column.primary && initial));

  return (
    <form className={styles.form} onSubmit={handleSubmit}>
      {title ? <h3 className={styles.title}>{title}</h3> : null}
      {editableColumns.map((column) => (
        <Input
          key={column.name}
          label={`${column.name}${column.primary ? ' (PK)' : ''}`}
          name={column.name}
          value={values[column.name] ?? ''}
          onChange={(event) => onFieldChange(column.name, event.target.value)}
          disabled={submitting}
          error={errors[column.name]}
        />
      ))}
      <div className={styles.actions}>
        <Button type="submit" loading={submitting}>
          Save
        </Button>
        <Button type="button" variant="secondary" onClick={onCancel} disabled={submitting}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
