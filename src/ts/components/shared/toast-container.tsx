import { useToastStore } from "@ts/stores";
import { X } from "lucide-react";
import { useEffect, useRef } from "react";

interface ToastProps {
  top?: boolean
  bottom?: boolean
  left?: boolean
  right?: boolean
}

export default function ToastContainer({ top, bottom, left, right }: Readonly<ToastProps>) {

  const { toasts, pullToast } = useToastStore()
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (toasts.length > 0) {
      ref.current?.showPopover()
    }
  }, [toasts])

  const positionClasses = [
    top ? 'toast-top' : '',
    bottom ? 'toast-bottom' : '',
    left ? 'toast-start' : '',
    right ? 'toast-end' : '',
  ].join(' ')

  return (
    <div
      ref={ref}
      popover="manual"
      className={`toast ${positionClasses} pointer-events-none border-none bg-transparent p-0 overflow-visible`}
    >
      {toasts.map((toast) => (
        <div key={toast.id} className={[
          'alert pointer-events-auto',
          toast.type === 'success' ? 'alert-success' : '',
          toast.type === 'error' ? 'alert-error' : '',
          toast.type === 'info' ? 'alert-info' : '',
          toast.type === 'warning' ? 'alert-warning' : '',
        ].join(' ')}>
          {toast.persistent ? null : <button className="btn btn-sm btn-ghost" onClick={() => pullToast(toast.id)} title="Close"><X size={16} /></button>}
          {toast.title && <span className="font-bold">{toast.title}</span>}
          <span>{toast.message}</span>
        </div>
      ))}
    </div>
  );
}
