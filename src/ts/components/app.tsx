import { useUiStore } from '@ts/stores';
import { useRef } from 'react';
import ActionBar from './layout/action-bar';
import Breadcrumb from './layout/breadcrumb';
import ContentView from './layout/content-view';
import ContextBar from './layout/selection-bar/selection-bar';
import TreeView from './layout/tree-view/tree-view';
import ModalContainer from './modals/modal-container';
import { ContextualMenuContainer } from './shared/contextual-menu-container';
import ToastContainer from './shared/toast-container';
import { useAppInitialization } from './shared/use-app-initialization';
import { useContextMenuTrigger } from './shared/use-contextual-menu-trigger';
import { useKeyboardNavigation } from './shared/use-keyboard-navigation';
import { useThemeInitialization } from './shared/use-theme-initialization';
import GlobalLoader from './layout/global-loader';


function App() {
  const appRef = useRef<HTMLDivElement | null>(null)
  const { treeVisible } = useUiStore()
  useAppInitialization()
  useThemeInitialization()
  useKeyboardNavigation()
  useContextMenuTrigger(appRef)

  return (
    <div ref={appRef} className="h-screen bg-base-200 flex flex-col overflow-hidden">
      {/* Navbar + action bar */}
      <ActionBar />

      {/* Selection bar */}
      <ContextBar />

      {/* Main layout */}
      <div className="flex flex-row gap-4 p-4 flex-1 min-h-0">
        {treeVisible && (
          <div className="w-64 overflow-hidden flex flex-col">
            <TreeView />
          </div>
        )}
        <div className="flex flex-col gap-2 flex-1 min-w-0 overflow-hidden">
          <div className="bg-base-100 rounded-box px-4 py-2 shadow-sm shrink-0 flex items-center justify-between gap-2">
            <div className="flex-1 min-w-0 overflow-hidden">
              <Breadcrumb />
            </div>
            <div className="shrink-0">
              <GlobalLoader />
            </div>
          </div>
          <div className="bg-base-100 rounded-box shadow-sm flex-1 overflow-hidden">
            <ContentView />
          </div>
        </div>
      </div>

      <ToastContainer top right />
      <ContextualMenuContainer />

      {/* Modals */}
      <ModalContainer />
    </div>
  )
}

export default App
