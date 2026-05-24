import { FieldError as ReactHookFormFieldError } from "react-hook-form";

interface FieldErrorProps {
  fieldError: ReactHookFormFieldError | undefined
}

export default function FieldError({ fieldError }: Readonly<FieldErrorProps>) {

  return (
    <>
      {fieldError && <small className={[
        'validator-hint mx-auto my-0',
        fieldError.message ? 'visible text-[var(--color-error)]' : ''
      ].join(' ')}>{fieldError.message}</small>}
    </>
  );
}
