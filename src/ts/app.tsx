import { useContainer } from '@ts/container/container-context';
import { useFileManagerStore } from '@ts/stores/file-manager-store';
import { Component, useCallback } from 'react';
import type { Path, TreeNodeDirectory, TreeNodeFile } from './types';
import { rootPath } from './types';

type AppViewProps = {
    currentPath: Path
    directories: TreeNodeDirectory[]
    files: TreeNodeFile[]
    setCurrentPath: (path: Path) => void
    loadFiles: (path: Path) => Promise<void>
    initialize: () => Promise<void>
}

class AppView extends Component<AppViewProps> {
    private initialized = false

    override componentDidMount(): void {
        if (this.initialized) {
            return
        }

        this.initialized = true

        void (async () => {
            try {
                await this.props.initialize().then(() => {
                  return this.props.loadFiles(this.props.currentPath)
                })
            } catch (error) {
                console.error('Error initializing FileManagerService', error)
            }
        })()
    }

    override render() {
        const { currentPath, directories, files, setCurrentPath, loadFiles } = this.props

        return (
            <div className="min-h-screen bg-gray-100 p-8">
                <h1 className="text-2xl font-bold mb-4">Storage Manager</h1>

                {/* Zustand : affiche le chemin courant */}
                <p className="mb-2 text-sm text-gray-600">
                    Chemin courant : <code>{currentPath}</code>
                </p>
                <div className="flex gap-2 mb-4">
                    <button
                        className="px-3 py-1 bg-blue-600 text-white rounded"
                        onClick={() => setCurrentPath(rootPath())}
                    >
                        Racine
                    </button>
                    <button
                        className="px-3 py-1 bg-green-600 text-white rounded"
                        onClick={() => void loadFiles(currentPath)}
                    >
                        Charger les fichiers
                    </button>
                </div>

                {/* Affiche les répertoires depuis le store */}
                <ul className="space-y-1 mb-4">
                    {directories.map((d) => (
                        <li key={d.path} className="p-2 bg-gray-200 rounded">
                            📁 {d.path} {d.hasSubDirectories ? '(sous-dossiers)' : ''}
                        </li>
                    ))}
            </ul>

                {/* Affiche les fichiers depuis le store */}
                <ul className="space-y-1">
                    {files.map((f) => (
                        <li key={f.path} className="p-2 bg-gray-200 rounded">
                            📄 {f.path} ({f.size} bytes, extension: {f.extension ?? 'N/A'})
                        </li>
                    ))}
                </ul>
            </div>
        )
    }
}

function App() {
    const { currentPath, directories, files, setCurrentPath } = useFileManagerStore()
    const container = useContainer()
    const fileApiService = container.cradle.fileManagerService

    const loadFiles = useCallback(async (path: Path) => {
        try {
            const { directories, files } = await fileApiService.listFiles(path)
            useFileManagerStore.setState({ directories, files })
        } catch (error) {
            console.error('Error loading files', error)
        }
    }, [fileApiService])

    const initialize = useCallback(async () => {
        await fileApiService.initialize()
    }, [fileApiService])

    return (
        <AppView
            currentPath={currentPath}
            directories={directories}
            files={files}
            setCurrentPath={setCurrentPath}
            loadFiles={loadFiles}
            initialize={initialize}
        />
    )
}

export default App
