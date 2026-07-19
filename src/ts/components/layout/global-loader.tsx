import { ActionStatus, useActionStore, useUploadStore } from "@ts/stores";
import { CheckCircle2, Loader2, ListTodo, XCircle } from "lucide-react";
import { useEffect, useState } from "react";

interface GroupedAction {
  id: string;
  type: string;
  destinationPath?: string;
  total: number;
  completed: number;
  errorCount: number;
  errors: string[];
  status: ActionStatus;
  title: string;
}

export default function GlobalLoader() {
  const { actions, clearCompleted: clearCompletedActions } = useActionStore()
  const { uploads, clearCompleted: clearCompletedUploads } = useUploadStore()

  const pendingActionCount = actions.filter(a => a.status === 'pending').length
  const pendingUploadCount = uploads.filter(u => ['pending', 'uploading', 'assembling'].includes(u.status)).length
  const pendingCount = pendingActionCount + pendingUploadCount
  const isSpinning = pendingCount > 0

  const [debouncedSpinning, setDebouncedSpinning] = useState(false);
  useEffect(() => {
    const timeout = setTimeout(
      () => setDebouncedSpinning(isSpinning),
      isSpinning ? 200 : 0
    );
    return () => clearTimeout(timeout);
  }, [isSpinning]);

  if (actions.length === 0 && uploads.length === 0) return (
    <div className="dropdown dropdown-end">
      <div tabIndex={0} role="button" className="btn btn-ghost btn-sm" title="Tasks">
        <ListTodo size={16} />
      </div>
      <ul tabIndex={0} className="dropdown-content z-50 menu p-4 shadow bg-base-100 rounded-box w-64 text-center text-sm text-base-content/60 border border-base-200">
        No background tasks
      </ul>
    </div>
  )

  const groupedActions: GroupedAction[] = [];
  for (const action of actions) {
    const lastGroup = groupedActions[groupedActions.length - 1];
    if (lastGroup && lastGroup.type === action.type && lastGroup.destinationPath === action.destinationPath) {
      lastGroup.total++;
      if (action.status !== 'pending') lastGroup.completed++;
      if (action.status === 'error') {
        lastGroup.errorCount++;
        if (action.error) lastGroup.errors.push(action.error);
      }
      
      if (lastGroup.errorCount > 0 && lastGroup.completed === lastGroup.total) lastGroup.status = 'error';
      else if (lastGroup.completed < lastGroup.total) lastGroup.status = 'pending';
      else lastGroup.status = 'success';
      
      const typeStr = action.type === 'DELETE' ? 'Deleting' : action.type === 'MOVE' ? 'Moving' : action.type === 'RENAME' ? 'Renaming' : action.type === 'CREATE' ? 'Creating' : 'Copying';
      lastGroup.title = `${typeStr} ${lastGroup.total} items`;
    } else {
      const typeStr = action.type === 'DELETE' ? 'Deleting' : action.type === 'MOVE' ? 'Moving' : action.type === 'RENAME' ? 'Renaming' : action.type === 'CREATE' ? 'Creating' : 'Copying';
      groupedActions.push({
        id: action.id,
        type: action.type,
        destinationPath: action.destinationPath,
        total: 1,
        completed: action.status !== 'pending' ? 1 : 0,
        errorCount: action.status === 'error' ? 1 : 0,
        errors: action.status === 'error' && action.error ? [action.error] : [],
        status: action.status,
        title: `${typeStr} 1 item`
      });
    }
  }

  const hasActionError = actions.some(a => a.status === 'error');
  const hasUploadError = uploads.some(u => u.status === 'error');
  const hasError = hasActionError || hasUploadError;

  return (
    <div className="dropdown dropdown-end">
      <div tabIndex={0} role="button" className="btn btn-sm btn-ghost">
        {debouncedSpinning ? <Loader2 className="animate-spin text-primary" size={16} /> :
         hasError ? <XCircle className="text-error" size={16} /> :
         <CheckCircle2 className="text-success" size={16} />}
        {pendingCount > 0 && <span className="badge badge-sm badge-primary">{pendingCount}</span>}
      </div>
      <div tabIndex={0} className="dropdown-content z-50 bg-base-100 shadow-xl rounded-lg p-4 w-80 border border-base-200 flex flex-col gap-3 max-h-96 overflow-y-auto mt-2">
        <div className="flex items-center justify-between border-b border-base-200 pb-2">
          <h3 className="font-bold text-sm">Background Tasks</h3>
          <button 
            className="btn btn-xs btn-ghost text-base-content/60" 
            onClick={() => { clearCompletedActions(); clearCompletedUploads(); }}
            disabled={actions.every(a => a.status === 'pending') && uploads.every(u => ['pending', 'uploading', 'assembling'].includes(u.status))}
          >
            Clear Done
          </button>
        </div>
        
        <div className="flex flex-col gap-2">
          {uploads.map(upload => (
            <div key={upload.id} className="flex flex-col gap-1 text-sm bg-base-200/50 p-2 rounded">
              <div className="flex justify-between items-center">
                <div className="min-w-0 flex items-center gap-2 pr-2">
                  <span className="font-medium truncate" title={upload.fileName}>Upload: {upload.fileName}</span>
                  <span className="badge badge-outline badge-xs shrink-0" title={`Upload disk: ${upload.disk}`}>
                    {upload.disk}
                  </span>
                </div>
                {['pending', 'uploading', 'assembling'].includes(upload.status) && <Loader2 className="animate-spin text-primary" size={14} />}
                {upload.status === 'success' && <CheckCircle2 className="text-success" size={14} />}
                {upload.status === 'error' && <XCircle className="text-error" size={14} />}
              </div>
              {['pending', 'uploading', 'assembling'].includes(upload.status) && (
                <progress 
                  className={`progress w-full ${upload.status === 'assembling' ? 'progress-secondary' : 'progress-primary'}`} 
                  value={upload.progress} 
                  max="100"
                ></progress>
              )}
              {upload.status === 'error' && upload.error && (
                <span className="text-xs text-error truncate">{upload.error}</span>
              )}
            </div>
          ))}

          {groupedActions.map(group => (
            <div key={group.id} className="flex flex-col gap-1 text-sm bg-base-200/50 p-2 rounded">
              <div className="flex justify-between items-center">
                <span className="font-medium truncate pr-2">{group.title}</span>
                {group.status === 'pending' && <Loader2 className="animate-spin text-primary" size={14} />}
                {group.status === 'success' && <CheckCircle2 className="text-success" size={14} />}
                {group.status === 'error' && <XCircle className="text-error" size={14} />}
              </div>
              {group.status === 'pending' && (
                <progress 
                  className="progress progress-primary w-full" 
                  value={group.completed} 
                  max={group.total}
                ></progress>
              )}
              {group.status === 'error' && group.errors.length > 0 && (
                <span className="text-xs text-error truncate">{group.errors[0]}</span>
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
