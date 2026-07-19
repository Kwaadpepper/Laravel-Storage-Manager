import { ModalState, useDiskStore, useFileManagerStore, useUiStore, useUploadStore, UploadItem } from "@ts/stores";
import { useContainer } from "@ts/container";
import { CheckCircle2, Clock, FileUp, FolderOpen, Loader2, X, XCircle } from "lucide-react";
import { useRef, useState } from "react";

export default function UploadModal() {
  const { uploadFileModal, setUploadFileModal } = useUiStore();
  const { currentDisk } = useDiskStore();
  const { currentPath } = useFileManagerStore();
  const { uploads, clearCompleted } = useUploadStore();
  const container = useContainer();
  const uploadService = container.resolve('uploadService');
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [isDraggingOver, setIsDraggingOver] = useState(false);

  const isOpen = uploadFileModal === ModalState.Opened;

  // We don't return null if not open, because we rely on DaisyUI's modal-open class for animation.
  // But actually the ModalContainer conditionally renders this component based on useDelayedUnmount.
  
  const hasActiveUploads = uploads.some(u => ['pending', 'uploading', 'assembling'].includes(u.status));

  const onClose = () => {
    setUploadFileModal(ModalState.Closed);
  };

  // Don't close the modal via backdrop when uploads are in progress
  const onBackdropClose = () => {
    if (!hasActiveUploads) {
      onClose();
    }
  };

  const onFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0 && currentDisk) {
      uploadService.enqueueUploads(Array.from(e.target.files), currentPath, currentDisk);
    }
    // reset input so the same files can be selected again
    e.target.value = '';
  };

  const onDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    if (e.dataTransfer.types.includes('Files')) setIsDraggingOver(true);
  };

  const onDragLeave = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDraggingOver(false);
  };

  const onDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDraggingOver(false);
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0 && currentDisk) {
      uploadService.enqueueUploads(Array.from(e.dataTransfer.files), currentPath, currentDisk);
    }
  };

  const activeUploads = uploads.filter(u => ['uploading', 'assembling'].includes(u.status)).reverse();
  const finishedUploads = uploads.filter(u => ['success', 'error'].includes(u.status)).reverse();
  const pendingUploads = uploads.filter(u => u.status === 'pending').reverse();
  const mainList = [...activeUploads, ...finishedUploads];

  const renderUploadRow = (upload: UploadItem) => (
    <div key={upload.id} className="bg-base-200/50 p-3 rounded-lg flex flex-col gap-2">
      <div className="flex justify-between items-center">
        <div className="min-w-0 flex flex-wrap items-center gap-2 pr-2">
          <span className="font-medium truncate text-sm" title={upload.fileName}>{upload.fileName}</span>
          {upload.finalFileName && upload.finalFileName !== upload.fileName && (
            <span className="text-xs opacity-70 truncate" title={`Saved as: ${upload.finalFileName}`}>
              → {upload.finalFileName}
            </span>
          )}
          <span className="badge badge-outline badge-sm shrink-0" title={`Upload disk: ${upload.disk}`}>
            {upload.disk}
          </span>
        </div>
        <div className="flex items-center gap-2 shrink-0">
          {upload.status === 'pending' && (
            <>
              <span className="text-xs opacity-50 italic">En attente...</span>
              <Clock className="text-base-content/50" size={16} />
            </>
          )}
          {['uploading', 'assembling'].includes(upload.status) && (
            <>
              <span className="text-xs opacity-70">{upload.progress}%</span>
              <Loader2 className="animate-spin text-primary" size={16} />
            </>
          )}
          {upload.status === 'success' && <CheckCircle2 className="text-success" size={16} />}
          {upload.status === 'error' && <XCircle className="text-error" size={16} />}
        </div>
      </div>
      {['uploading', 'assembling'].includes(upload.status) && (
        <progress 
          className={`progress w-full ${upload.status === 'assembling' ? 'progress-secondary' : 'progress-primary'}`} 
          value={upload.progress} 
          max="100"
        ></progress>
      )}
      {upload.status === 'pending' && (
        <progress className="progress w-full opacity-20" value="0" max="100"></progress>
      )}
      {upload.status === 'error' && upload.error && (
        <span className="text-xs text-error truncate">{upload.error}</span>
      )}
    </div>
  );

  return (
    <dialog className={`modal ${isOpen ? 'modal-open' : ''}`}>
      <div className="modal-box max-w-2xl flex flex-col max-h-[80vh]">
        <form method="dialog">
          <button className="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" onClick={onClose} type="button">
            <X size={18} />
          </button>
        </form>
        <h3 className="font-bold text-lg flex items-center gap-2 mb-4">
          <FileUp />
          Upload Files
        </h3>
        
        {/* Dropzone */}
        <div 
          className={`border-2 border-dashed rounded-xl p-8 flex flex-col items-center justify-center transition-colors cursor-pointer text-center
            ${isDraggingOver ? 'border-primary bg-primary/10 text-primary' : 'border-base-300 hover:border-primary/50 hover:bg-base-200'}
          `}
          onClick={() => fileInputRef.current?.click()}
          onDragOver={onDragOver}
          onDragLeave={onDragLeave}
          onDrop={onDrop}
        >
          <input 
            type="file" 
            multiple 
            className="hidden" 
            ref={fileInputRef} 
            onChange={onFileSelect}
          />
          <FolderOpen size={48} className="mb-2 opacity-70" />
          <p className="font-medium text-lg">Click to browse or drop files here</p>
          <p className="text-sm opacity-70">Uploading to: {currentPath}</p>
        </div>

        {/* Upload list */}
          <div className="mt-6 flex-1 min-h-0 flex flex-col">
            <div className="flex items-center justify-between mb-2">
              <h4 className="font-semibold text-sm">Upload Tasks</h4>
              <button 
                className="btn btn-xs btn-ghost text-base-content/60" 
                onClick={clearCompleted}
                disabled={uploads.every(u => ['pending', 'uploading', 'assembling'].includes(u.status))}
              >
                Clear Done
              </button>
            </div>
            <div className="overflow-y-auto flex-1 pr-2 space-y-4">
              
              {pendingUploads.length > 0 && (
                <div className="collapse collapse-arrow bg-base-200/30 border border-base-300 rounded-lg">
                  <input type="checkbox" /> 
                  <div className="collapse-title font-medium text-sm text-base-content/70 py-3 min-h-0">
                    Fichiers en attente ({pendingUploads.length})
                  </div>
                  <div className="collapse-content space-y-2"> 
                    {pendingUploads.map(renderUploadRow)}
                  </div>
                </div>
              )}

              {mainList.length > 0 && (
                <div className="space-y-2">
                  {mainList.map(renderUploadRow)}
                </div>
              )}
            </div>
          </div>
      </div>
      <form method="dialog" className="modal-backdrop">
        <button onClick={onBackdropClose} type="button">close</button>
      </form>
    </dialog>
  );
}
