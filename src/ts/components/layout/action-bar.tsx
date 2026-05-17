import { useContainer } from "@ts/container";
import { useFileManagerStore } from "@ts/stores";
import { ArrowLeft, ArrowRight, ArrowUp } from "lucide-react";

type ActionBarProps = {
}

export default function ActionBar(_: ActionBarProps) {
  const container = useContainer()
  const { setAboutModal } = useUiStore()

  const navigationService = container.cradle.navigationService
  const canNavigatePrevious = navigationService.canNavigatePrevious()
  const canNavigateNext = navigationService.canNavigateNext()
  const canNavigateUp = navigationService.canNavigateUp()

  function onBackClick() {
    navigationService.navigatePrevious()
  }

  function onForwardClick() {
    navigationService.navigateNext()
  }

  function onUpClick() {
    navigationService.navigateToParent()
  }

  function onAboutClick() {
    setAboutModal(ModalState.Opened)
  }

  return (
    <div className="flex">
      <div className="flex-1">
        <button className="btn btn-ghost btn-sm" onClick={onBackClick} disabled={!canNavigatePrevious}>
          <ArrowLeft size={16} />Back
        </button>
        <button className="btn btn-ghost btn-sm" onClick={onForwardClick} disabled={!canNavigateNext}>
          <ArrowRight size={16} />Forward
        </button>
        <button className="btn btn-ghost btn-sm" onClick={onUpClick} disabled={!canNavigateUp}>
          <ArrowUp size={16} />Up
        </button>
      </div>

      <div className="flex-1 ms-auto">
      <div className="flex-1 ms-auto">
        <button className="btn btn-ghost btn-sm" title="About" onClick={onAboutClick}>
          <CircleQuestionMark size={16} />
        </button>
      </div>
    </div>
  );
}
