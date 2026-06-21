import { useContainer } from "@ts/container";
import { ModalState, useConfigStore, useDiskStore, useFileManagerStore, useUiStore } from "@ts/stores";
import { ArrowLeft, ArrowRight, ArrowUp, CircleQuestionMark, FilePlus, FileUp, FolderPlus, Link, Maximize, Minimize, PanelLeft, PanelLeftClose, RefreshCw } from "lucide-react";
import { useEffect, useState } from "react";
import ThemeSelector from "./theme-selector";

function toggleFullscreen() {
  if (globalThis.document.fullscreenElement) {
    globalThis.document.exitFullscreen();
  } else {
    globalThis.document.documentElement.requestFullscreen().catch((err) => {
      console.error(`Error attempting to enable fullscreen: ${err.message}`);
    });
  }
}

function listenFullscreenChange(callback: () => void) {
  globalThis.document.addEventListener('fullscreenchange', callback);
  return () => globalThis.document.removeEventListener('fullscreenchange', callback);
}

function isFullscreenActive(): boolean {
  return !!globalThis.document.fullscreenElement;
}

interface ActionBarProps {
}


export default function ActionBar(_: Readonly<ActionBarProps>) {
  const container = useContainer()
  const { setAboutModal, setCreateFileModal, setNewDirectoryModal, setUploadFileModal, treeVisible, setTreeVisible } = useUiStore()
  const { canNavigatePrevious, canNavigateNext, canNavigateUp } = useFileManagerStore()

  const navigationService = container.resolve('navigationService')

  const { readOnlyDisks } = useConfigStore()
  const { currentDisk } = useDiskStore()
  const currentPath = useFileManagerStore(s => s.currentPath)
  const isReadOnly = currentDisk ? readOnlyDisks.includes(currentDisk) : false
  const toastService = container.resolve('toastService')

  const [isFullscreen, setIsFullscreen] = useState(false)

  useEffect(() => {
    function onFullscreenChange() {
      setIsFullscreen(isFullscreenActive())
    }

    return listenFullscreenChange(onFullscreenChange)
  }, [])

  function onReloadClick() {
    navigationService.refreshCurrentPath()
  }

  function onBackClick() {
    navigationService.navigatePrevious()
  }

  function onForwardClick() {
    navigationService.navigateNext()
  }

  function onUpClick() {
    navigationService.navigateToParent()
  }

  function onNewFileClick() {
    setCreateFileModal(ModalState.Opened)
  }

  function onNewDirectoryClick() {
    setNewDirectoryModal(ModalState.Opened)
  }

  function onUploadFileClick() {
    setUploadFileModal(ModalState.Opened)
  }

  function onAboutClick() {
    setAboutModal(ModalState.Opened)
  }

  function onCopyPathClick() {
    navigator.clipboard.writeText(currentPath).catch(() => {})
    toastService.pushToast({ message: 'Path copied to clipboard.', type: 'success' })
  }

  function onCopyLinkClick() {
    const url = new URL(globalThis.location.href)
    const disk = currentDisk
    const hashPath = `/${disk}${currentPath === '/' ? '' : currentPath}`
    url.hash = hashPath.split('/').map(encodeURIComponent).join('/')
    navigator.clipboard.writeText(url.toString()).catch(() => {})
    toastService.pushToast({ message: 'Link copied to clipboard.', type: 'success' })
  }

  return (
    <div className="navbar bg-base-100 shadow-sm px-4">
      {/* Navigation */}
      <div className="navbar-start gap-1">
        <button
          className="btn btn-ghost btn-sm"
          title={treeVisible ? 'Hide tree' : 'Show tree'}
          onClick={() => setTreeVisible(!treeVisible)}
        >
          {treeVisible ? <PanelLeftClose size={16} /> : <PanelLeft size={16} />}
        </button>
        <button className="btn btn-ghost btn-sm" title="Back" onClick={onBackClick} disabled={!canNavigatePrevious}>
          <ArrowLeft size={16} />
          <span className="hidden md:inline">Back</span>
        </button>
        <button className="btn btn-ghost btn-sm" title="Forward" onClick={onForwardClick} disabled={!canNavigateNext}>
          <ArrowRight size={16} />
          <span className="hidden md:inline">Forward</span>
        </button>
        <button className="btn btn-ghost btn-sm" title="Up" onClick={onUpClick} disabled={!canNavigateUp}>
          <ArrowUp size={16} />
          <span className="hidden md:inline">Up</span>
        </button>
        <button className="btn btn-ghost btn-sm" title="Reload" onClick={onReloadClick}>
          <RefreshCw size={16} />
          <span className="hidden md:inline">Reload</span>
        </button>
      </div>

      {/* Actions */}
      <div className="navbar-center gap-1">
        <button className="btn btn-ghost btn-sm" title="New File" onClick={onNewFileClick} disabled={isReadOnly}>
          <FilePlus size={16} />
          <span className="hidden md:inline">New File</span>
        </button>
        <button className="btn btn-ghost btn-sm" title="New Directory" onClick={onNewDirectoryClick} disabled={isReadOnly}>
          <FolderPlus size={16} />
          <span className="hidden md:inline">New Directory</span>
        </button>
        <button className="btn btn-ghost btn-sm" title="Upload File" onClick={onUploadFileClick} disabled={isReadOnly}>
          <FileUp size={16} />
          <span className="hidden md:inline">Upload File</span>
        </button>

        <div className="dropdown dropdown-bottom">
          <div tabIndex={0} role="button" className="btn btn-ghost btn-sm" title="Copy Links">
            <Link size={16} />
            <span className="hidden md:inline">URL</span>
          </div>
          <ul tabIndex={0} className="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
            <li><a onClick={onCopyPathClick}>Copy Path</a></li>
            <li><a onClick={onCopyLinkClick}>Copy Link</a></li>
          </ul>
        </div>
      </div>

      {/* View Mode and Theme */}
      <div className="navbar-end gap-1">
        <button
          className="btn btn-ghost btn-sm"
          title={isFullscreen ? 'Exit full screen' : 'Full screen'}
          onClick={toggleFullscreen}
        >
          {isFullscreen ? <Minimize size={16} /> : <Maximize size={16} />}
        </button>
        <ThemeSelector />
        <button className="btn btn-ghost btn-sm" title="About" onClick={onAboutClick}>
          <CircleQuestionMark size={16} />
          <span className="hidden md:inline">About</span>
        </button>
      </div>
    </div>
  );
}
