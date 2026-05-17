import { FieldError as ReactHookFormFieldError } from "react-hook-form";

type FieldErrorProps = {
  readonly fieldError: ReactHookFormFieldError | undefined
}

export default function FieldError({ fieldError }: FieldErrorProps) {

  return (
    <>
      {fieldError && <small className={[
        'validator-hint mx-auto my-0',
        fieldError.message ? 'visible text-[var(--color-error)]' : ''
      ].join(' ')}>{fieldError.message}</small>}
    </>
  );
}
