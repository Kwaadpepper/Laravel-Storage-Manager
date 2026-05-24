import { useToastStore } from "@ts/stores";
import { X } from "lucide-react";

interface ToastProps {
  top?: boolean
  bottom?: boolean
  left?: boolean
  right?: boolean
}

export default function ToastContainer({ top, bottom, left, right }: Readonly<ToastProps>) {

  const { toasts, pullToast } = useToastStore()

  const positionClasses = [
    top ? 'toast-top' : '',
    bottom ? 'toast-bottom' : '',
    left ? 'toast-start' : '',
    right ? 'toast-end' : '',
  ].join(' ')

  return (
    <>
      {toasts.length > 0 && <div className={`toast ${positionClasses}`}>
        {toasts.map((toast) => (
          <div key={toast.id} className={[
            'alert',
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
      </div>}
    </>
  );
}
