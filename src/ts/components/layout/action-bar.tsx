import { useContainer } from "@ts/container";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { ArrowLeft, ArrowRight, ArrowUp, CircleQuestionMark, FilePlus, FileUp, FolderPlus, LayoutGrid, List } from "lucide-react";
import ThemeSelector from "./theme-selector";

interface ActionBarProps {
}

export default function ActionBar(_: Readonly<ActionBarProps>) {
  const container = useContainer()
  const { setAboutModal, setCreateFileModal, setNewDirectoryModal, setUploadFileModal, viewMode, setViewMode } = useUiStore()
  const { canNavigatePrevious, canNavigateNext, canNavigateUp } = useFileManagerStore()

  const navigationService = container.resolve('navigationService')

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

  return (
    <div className="navbar bg-base-100 shadow-sm px-4">
      <div className="navbar-start gap-1">
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
      </div>

      <div className="navbar-center gap-1">
        <button className="btn btn-ghost btn-sm" title="New File" onClick={onNewFileClick}>
          <FilePlus size={16} />
          <span className="hidden md:inline">New File</span>
        </button>
        <button className="btn btn-ghost btn-sm" title="New Directory" onClick={onNewDirectoryClick}>
          <FolderPlus size={16} />
          <span className="hidden md:inline">New Directory</span>
        </button>
        <button className="btn btn-ghost btn-sm" title="Upload File" onClick={onUploadFileClick}>
          <FileUp size={16} />
          <span className="hidden md:inline">Upload File</span>
        </button>
      </div>

      <div className="navbar-end gap-1">
        <button
          className="btn btn-ghost btn-sm"
          title={viewMode === 'list' ? 'List view' : 'Tiles view'}
          onClick={() => setViewMode(viewMode === 'list' ? 'tiles' : 'list')}
        >
          {viewMode === 'list' ? <List size={16} /> : <LayoutGrid size={16} />}
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

