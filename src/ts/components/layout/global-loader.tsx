import { ActionItem, ActionStatus, useActionStore } from "@ts/stores";
import { CheckCircle2, ChevronDown, Loader2, ListTodo, XCircle } from "lucide-react";
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
  const { actions, clearCompleted } = useActionStore()

  const pendingCount = actions.filter(a => a.status === 'pending').length
  const isSpinning = pendingCount > 0

  const [debouncedSpinning, setDebouncedSpinning] = useState(false);
  useEffect(() => {
    let timeout: ReturnType<typeof setTimeout>;
    if (isSpinning) {
      timeout = setTimeout(() => setDebouncedSpinning(true), 200);
    } else {
      setDebouncedSpinning(false);
    }
    return () => clearTimeout(timeout);
  }, [isSpinning]);

  if (actions.length === 0) return (
    <div className="dropdown dropdown-end">
      <div tabIndex={0} role="button" className="btn btn-ghost btn-sm" title="Tasks">
        <ListTodo size={16} />
      </div>
      <ul tabIndex={0} className="dropdown-content z-[1] menu p-4 shadow bg-base-100 rounded-box w-64 text-center text-sm text-base-content/60 border border-base-200">
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

  const hasError = actions.some(a => a.status === 'error');

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
            onClick={clearCompleted}
            disabled={actions.every(a => a.status === 'pending')}
          >
            Clear Done
          </button>
        </div>
        
        <div className="flex flex-col gap-2">
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
