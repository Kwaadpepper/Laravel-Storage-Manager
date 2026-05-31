import { ModalState, useConfigStore, useUiStore } from "@ts/stores";
import { X } from "lucide-react";
import { useEffect, useRef } from "react";

interface AboutModalProps {
}

export default function AboutModal(_: Readonly<AboutModalProps>) {

  const { aboutModal, setAboutModal } = useUiStore()
  const { packageName, packageVersion, packageLogo, composerPackageName, appDescription, appAuthors } = useConfigStore()

  const dialogRef = useRef<HTMLDialogElement | null>(null)
  const closeButtonRef = useRef<HTMLButtonElement | null>(null)
  const closeInnerButtonRef = useRef<HTMLButtonElement | null>(null)

  useEffect(() => {
    if (aboutModal === ModalState.Opened) {
      dialogRef?.current?.showModal()
    } else {
      dialogRef?.current?.close()
    }
  }, [aboutModal])

  useEffect(() => {
    closeInnerButtonRef.current?.addEventListener('click', onInnerCloseButtonClick)
    return () => {
      closeInnerButtonRef.current?.removeEventListener('click', onInnerCloseButtonClick)
    }
  }, [])


  function onInnerCloseButtonClick() {
    closeButtonRef.current?.click()
  }

  return (
    <dialog className="modal" ref={dialogRef} onClose={() => setAboutModal(ModalState.Closed)}>
      <div className="modal-box">
        <button className="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" ref={closeInnerButtonRef}>
          <X size={16} />
        </button>
        <h3 className="font-bold text-lg">About</h3>
        <div className="flex items-center justify-center py-4">
          <img src={packageLogo} alt={`${packageName} logo`} className="w-16 h-16" />
        </div>
        <p className="py-4">
          <span className="font-bold">
            <span className="text-sm">{packageName}</span>
            <span className="text-sm text-base-content/40"> ({composerPackageName})</span>
          </span><br />
          <span className="text-sm"><span className="italic">Version&nbsp;:&nbsp;</span>{packageVersion}</span><br />
        </p>
        <p className="py-2">{appDescription}</p>
        <em className="ms-5 pb-4 block">
          <span className="italic">Contributors</span>
          <ul>
            {appAuthors.map((author, index) => (
              <li key={`author-${author.email}-${index}`}>
                <span className="font-bold">{author.name}</span> - <a href={`mailto:${author.email}`} className="link link-hover">
                  <span className="text-sm">{author.email}</span>
                </a>
              </li>
            ))}
          </ul>
        </em>
      </div>

      {/* Form to control the modal */}
      <form method="dialog" className="modal-backdrop">
        <button ref={closeButtonRef}>Close</button>
      </form>
    </dialog>
  );
}
