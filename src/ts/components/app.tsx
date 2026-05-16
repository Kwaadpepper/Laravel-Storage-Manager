import { useContainer } from '@ts/container';
import { rootPath } from '@ts/types';
import { useEffect } from 'react';
import ActionBar from './layout/action-bar';
import Breadcrumb from './layout/breadcrumb';
import ContentView from './layout/content-view';
import TreeView from './layout/tree-view';


function App() {
  const container = useContainer()
  const fileApiService = container.cradle.fileManagerService
  const navigationService = container.cradle.navigationService

  useEffect(() => {
    fileApiService.initialize().then(() => {
      navigationService.navigateTo(rootPath())
    }).catch(() => {
      console.error('Error initializing file manager')
    })
  }, [fileApiService, navigationService])

  return (
    <div className="min-h-screen bg-base-200">
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
    </div>
  )
}

export default App
