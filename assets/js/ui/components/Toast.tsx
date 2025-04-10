import React, { useEffect, useState } from 'react';
import theme from '../styles/theme';

export type ToastVariant = 'info' | 'success' | 'warning' | 'error';

export interface ToastProps {
  message: string;
  variant?: ToastVariant;
  duration?: number;
  onClose?: () => void;
  isVisible?: boolean;
}

export const Toast: React.FC<ToastProps> = ({
  message,
  variant = 'info',
  duration = 3000,
  onClose,
  isVisible = true,
}) => {
  const [visible, setVisible] = useState(isVisible);

  useEffect(() => {
    setVisible(isVisible);
  }, [isVisible]);

  useEffect(() => {
    if (visible && duration !== Infinity) {
      const timer = setTimeout(() => {
        setVisible(false);
        if (onClose) onClose();
      }, duration);

      return () => clearTimeout(timer);
    }
  }, [visible, duration, onClose]);

  if (!visible) return null;

  // Toast variant styles
  const variantStyles = {
    info: {
      backgroundColor: '#EBF8FF',
      color: '#3182CE',
      borderColor: '#BEE3F8',
      icon: 'ℹ️',
    },
    success: {
      backgroundColor: '#F0FFF4',
      color: '#38A169',
      borderColor: '#C6F6D5',
      icon: '✓',
    },
    warning: {
      backgroundColor: '#FFFAF0',
      color: '#DD6B20',
      borderColor: '#FEEBC8',
      icon: '⚠️',
    },
    error: {
      backgroundColor: '#FFF5F5',
      color: '#E53E3E',
      borderColor: '#FED7D7',
      icon: '✕',
    },
  };

  const toastStyle: React.CSSProperties = {
    position: 'fixed',
    bottom: theme.space['4'],
    right: theme.space['4'],
    maxWidth: '24rem',
    padding: `${theme.space['3']} ${theme.space['4']}`,
    borderRadius: theme.radii.md,
    boxShadow: theme.shadows.md,
    display: 'flex',
    alignItems: 'center',
    gap: theme.space['3'],
    zIndex: 1001,
    animation: 'slideIn 0.3s ease-out',
    border: `1px solid ${variantStyles[variant].borderColor}`,
    backgroundColor: variantStyles[variant].backgroundColor,
    color: variantStyles[variant].color,
  };

  const iconStyle: React.CSSProperties = {
    width: '20px',
    height: '20px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: theme.radii.full,
    flexShrink: 0,
  };

  const closeButtonStyle: React.CSSProperties = {
    background: 'none',
    border: 'none',
    color: 'currentColor',
    cursor: 'pointer',
    marginLeft: 'auto',
    padding: '0',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    width: '20px',
    height: '20px',
    opacity: 0.6,
  };

  const handleClose = () => {
    setVisible(false);
    if (onClose) onClose();
  };

  return (
    <div style={toastStyle} className={`ui-toast ui-toast-${variant}`} role="alert">
      <div style={iconStyle} className="ui-toast-icon">
        {variantStyles[variant].icon}
      </div>
      <div className="ui-toast-message">{message}</div>
      <button
        style={closeButtonStyle}
        className="ui-toast-close"
        onClick={handleClose}
        aria-label="Close"
      >
        ×
      </button>
    </div>
  );
};

// Create a Toast context and provider for global toast management
type ToastContextType = {
  showToast: (props: Omit<ToastProps, 'isVisible' | 'onClose'>) => void;
};

const ToastContext = React.createContext<ToastContextType | undefined>(undefined);

interface Toast extends ToastProps {
  id: string;
}

export const ToastProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [toasts, setToasts] = useState<Toast[]>([]);

  const showToast = (props: Omit<ToastProps, 'isVisible' | 'onClose'>) => {
    const id = Math.random().toString(36).substring(2, 9);
    setToasts((prev) => [...prev, { ...props, id, isVisible: true }]);
  };

  const handleClose = (id: string) => {
    setToasts((prev) => prev.filter((toast) => toast.id !== id));
  };

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      {toasts.map((toast) => (
        <Toast
          key={toast.id}
          message={toast.message}
          variant={toast.variant}
          duration={toast.duration}
          onClose={() => handleClose(toast.id)}
          isVisible={toast.isVisible}
        />
      ))}
    </ToastContext.Provider>
  );
};

// Hook for using toast
export const useToast = () => {
  const context = React.useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
};

export default Toast;