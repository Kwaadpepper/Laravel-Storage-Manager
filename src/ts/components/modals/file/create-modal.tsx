import { SUPPORTED_LANGUAGES } from "@ts/utils/supported-languages";
import { X } from "lucide-react";
import { lazy, Suspense, useEffect, useRef, useState } from "react";
import CreateFileAskFileName from "./create-modal-ask-file-name";
import { useCreateFileViewModel } from "./create-viewmodel";

const TextEditor = lazy(() => import("@ts/components/editor/text-editor"));

function detectLanguageFromExtension(ext: string): keyof typeof SUPPORTED_LANGUAGES {
  const normalized = ext.toLowerCase().replace(/^\./, '')
  for (const [label, aliases] of Object.entries(SUPPORTED_LANGUAGES)) {
    if ((aliases as readonly string[]).includes(normalized)) {
      return label as keyof typeof SUPPORTED_LANGUAGES
    }
  }
  return 'Text'
}

type CreateFileModalProps = Record<string, never>;

export default function CreateFileModal(_: Readonly<CreateFileModalProps>) {
  const {
    isOpen, close,
    baseName, setBaseName,
    extension, setExtension,
    baseNameMinLength, baseNameMaxLength, extensionMaxLength, validateFileName,
    submit,
  } = useCreateFileViewModel()

  const dialogRef = useRef<HTMLDialogElement | null>(null)
  const closeButtonRef = useRef<HTMLButtonElement | null>(null)

  const [step, setStep] = useState<'name' | 'editor'>('name')
  const [fileContent, setFileContent] = useState('')
  const [language, setLanguage] = useState<keyof typeof SUPPORTED_LANGUAGES>('Text')

  useEffect(() => {
    const current = dialogRef.current
    if (!current) return

    if (isOpen) {
      current.showModal()
    } else {
      current.close()
    }
  }, [isOpen])

  function onClose() {
    setBaseName(null)
    setExtension(null)
    setFileContent('')
    setLanguage('Text')
    setStep('name')
    close()
  }

  function onValidateFileName(name: string, ext: string) {
    return validateFileName(name, ext).then(errors =>
      errors?.map(({ field, message }) => ({ field, message }))
    )
  }

  function onConfirmFileName(confirmedBaseName: string, confirmedExtension: string) {
    setBaseName(confirmedBaseName)
    setExtension(confirmedExtension)
    setLanguage(detectLanguageFromExtension(confirmedExtension))
    setStep('editor')
  }

  function onCancelFileName() {
    close()
  }

  async function onCreate() {
    if (baseName === null || extension === null) return
    await submit(baseName, extension, fileContent)
  }

  let fileName: string | null = null
  if (baseName !== null) {
    fileName = extension ? `${baseName}.${extension}` : baseName
  }

  return (
    <dialog className="modal" onClose={onClose} ref={dialogRef}>
      <div
        className="modal-box flex flex-col"
        style={step === 'editor' ? { height: '90vh', width: '90vw', maxWidth: '90vw' } : {}}
      >
        <div className="flex items-center justify-between mb-2">
          <h3 className="font-bold text-lg">{fileName ? `Create - ${fileName}` : 'Create File'}</h3>
          <button
            className="btn btn-sm btn-circle btn-ghost"
            type="button"
            onClick={() => closeButtonRef.current?.click()}
          >
            <X size={16} />
          </button>
        </div>

        {step === 'name' && (
          <CreateFileAskFileName
            baseName={baseName ?? ''}
            extension={extension ?? ''}
            baseNameMinLength={baseNameMinLength}
            baseNameMaxLength={baseNameMaxLength}
            extensionMaxLength={extensionMaxLength}
            onValidate={onValidateFileName}
            onConfirm={onConfirmFileName}
            onCancel={onCancelFileName}
          />
        )}

        {step === 'editor' && (
          <>
            <div className="flex-1 min-h-0">
              <Suspense fallback={<div className="flex items-center justify-center h-full text-base-content/50">Loading editor…</div>}>
                <TextEditor
                  value={fileContent}
                  language={language}
                  onChange={setFileContent}
                  onLanguageChange={setLanguage}
                />
              </Suspense>
            </div>

            <div className="flex justify-end gap-2 mt-2">
              <button
                className="btn btn-primary"
                type="button"
                onClick={() => void onCreate()}
              >
                Create File
              </button>
              <button className="btn" type="button" onClick={() => closeButtonRef.current?.click()}>Cancel</button>
            </div>
          </>
        )}
      </div>

      <form method="dialog" className="modal-backdrop">
        <button ref={closeButtonRef}>Close</button>
      </form>
    </dialog>
  );
}
