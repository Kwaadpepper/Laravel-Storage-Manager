import { Pencil } from "lucide-react";
import { useState } from "react";

interface ValidationError {
  field: 'baseName' | 'extension'
  message: string
}

interface CreateFileAskFileNameProps {
  baseName: string
  extension: string
  baseNameMinLength: number
  baseNameMaxLength: number
  extensionMaxLength: number
  onValidate: (baseName: string, extension: string) => Promise<ValidationError[] | undefined>
  onConfirm: (baseName: string, extension: string) => void
  onCancel: () => void
}

export default function CreateFileAskFileName({
  baseName = '', extension = '',
  baseNameMinLength, baseNameMaxLength, extensionMaxLength,
  onValidate,
  onConfirm,
  onCancel,
}: Readonly<CreateFileAskFileNameProps>) {

  const [baseNameValue, setBaseNameValue] = useState(baseName)
  const [extensionValue, setExtensionValue] = useState(extension)

  function onSubmit(event: React.SubmitEvent<HTMLFormElement>) {
    event.preventDefault()
    const formData = new FormData(event.currentTarget)
    const submittedBaseName = formData.get('baseName') as string
    const submittedExtension = formData.get('extension') as string
    const form = event.currentTarget

    onValidate(submittedBaseName, submittedExtension).then(validationErrors => {
      if (!validationErrors || validationErrors.length === 0) {
        onConfirm(submittedBaseName, submittedExtension)
      } else {
        validationErrors.forEach(error => {
          const input = form.elements.namedItem(error.field) as HTMLInputElement
          input.setAttribute("aria-invalid", "true")
          input.setCustomValidity(error.message)
          input.reportValidity()
        })
      }
    })
  }

  return (
    <form className="fieldset flex flex-col justify-center gap-4 py-4" onSubmit={onSubmit}>
      <strong className="text-center block my-2">Name your file</strong>
      <div className="flex items-start gap-1 mx-auto">
        <div className="flex flex-col gap-1">
          <label className="input validator">
            <Pencil className="h-[1em] opacity-50" />
            <input
              className="grow"
              name="baseName"
              type="text"
              autoFocus
              autoComplete="off"
              minLength={baseNameMinLength}
              maxLength={baseNameMaxLength}
              value={baseNameValue}
              onChange={(e) => setBaseNameValue(e.target.value)}
              required
            />
          </label>
          <small className="text-center opacity-50">{`Base name (${baseNameMinLength}-${baseNameMaxLength} chars)`}</small>
        </div>

        <span className="self-center px-1 text-base-content/60 select-none">.</span>

        <div className="flex flex-col gap-1">
          <label className="input">
            <input
              className="grow w-24"
              name="extension"
              type="text"
              autoComplete="off"
              maxLength={extensionMaxLength}
              aria-label="Extension"
              value={extensionValue}
              onChange={(e) => setExtensionValue(e.target.value)}
            />
          </label>
          <small className="text-center opacity-50">{`Extension (max ${extensionMaxLength} chars)`}</small>
        </div>
      </div>

      <span className="flex justify-center">
        <button className="btn btn-primary me-2" type="submit">Next</button>
        <button className="btn btn-secondary ms-2" type="button" onClick={onCancel}>Cancel</button>
      </span>
    </form>
  );
}
