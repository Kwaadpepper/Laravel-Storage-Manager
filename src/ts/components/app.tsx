import { useRef } from 'react';
import ActionBar from './layout/action-bar';
import Breadcrumb from './layout/breadcrumb';
import ContentView from './layout/content-view';
import SelectionBar from './layout/selection-bar';
import TreeView from './layout/tree-view';
import ModalContainer from './modals/modal-container';
import { ContextualMenuContainer } from './shared/contextual-menu-container';
import ToastContainer from './shared/toast-container';
import { useAppInitialization } from './shared/use-app-initialization';
import { useContextMenuTrigger } from './shared/use-contextual-menu-trigger';
import { useThemeInitialization } from './shared/use-theme-initialization';


function App() {
  const appRef = useRef<HTMLDivElement | null>(null)
  useAppInitialization()
  useThemeInitialization()
  useContextMenuTrigger(appRef)

  return (
    <div ref={appRef} className="min-h-screen bg-base-200">
      {/* Navbar */}
      <div className="navbar bg-base-100 shadow-sm px-4">
        <div className="navbar-start">
          <span className="text-xl font-bold">Storage Manager</span>
        </div>
      </div>

      {/* Action bar */}
      <div className="bg-base-100 border-b border-base-300 px-4 py-1">
        <ActionBar />
      </div>

      {/* Selection bar */}
      <SelectionBar />

      {/* Main layout */}
      <div className="flex flex-row gap-4 p-4">
        <div className="w-64 shrink-0">
          <TreeView />
        </div>
        <div className="flex flex-col gap-2 flex-1 min-w-0">
          <div className="bg-base-100 rounded-box px-4 py-2 shadow-sm">
            <Breadcrumb />
          </div>
          <div className="bg-base-100 rounded-box p-4 shadow-sm flex-1">
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
