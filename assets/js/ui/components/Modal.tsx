import React, { useEffect, useRef } from 'react';
import theme from '../styles/theme';

export interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
  children: React.ReactNode;
  footer?: React.ReactNode;
}

export const Modal: React.FC<ModalProps> = ({
  isOpen,
  onClose,
  title,
  size = 'md',
  children,
  footer,
}) => {
  const modalRef = useRef<HTMLDivElement>(null);

  // Handle Escape key press
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  // Prevent scrolling when modal is open
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [isOpen]);

  // Handle click outside modal
  const handleOverlayClick = (e: React.MouseEvent<HTMLDivElement>) => {
    if (modalRef.current && !modalRef.current.contains(e.target as Node)) {
      onClose();
    }
  };

  // If modal is closed, don't render
  if (!isOpen) return null;

  // Size styles
  const sizeStyles = {
    sm: { maxWidth: '24rem' },  // 384px
    md: { maxWidth: '32rem' },  // 512px
    lg: { maxWidth: '48rem' },  // 768px
    xl: { maxWidth: '64rem' },  // 1024px
    full: { maxWidth: '100%', margin: theme.space['4'] },
  };

  // Styles
  const overlayStyle: React.CSSProperties = {
    position: 'fixed',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 1000,
  };

  const modalStyle: React.CSSProperties = {
    ...sizeStyles[size],
    width: '100%',
    backgroundColor: theme.colors.background,
    borderRadius: theme.radii.lg,
    boxShadow: theme.shadows.xl,
    display: 'flex',
    flexDirection: 'column',
    maxHeight: '90vh',
  };

  const headerStyle: React.CSSProperties = {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: `${theme.space['4']} ${theme.space['6']}`,
    borderBottom: `1px solid ${theme.colors.border}`,
  };

  const titleStyle: React.CSSProperties = {
    margin: 0,
    fontSize: theme.fontSizes.xl,
    fontWeight: 600,
    color: theme.colors.text,
  };

  const closeButtonStyle: React.CSSProperties = {
    background: 'none',
    border: 'none',
    cursor: 'pointer',
    fontSize: theme.fontSizes['2xl'],
    color: theme.colors.muted,
    padding: 0,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    width: '32px',
    height: '32px',
  };

  const bodyStyle: React.CSSProperties = {
    padding: theme.space['6'],
    overflowY: 'auto',
  };

  const footerStyle: React.CSSProperties = {
    display: 'flex',
    justifyContent: 'flex-end',
    padding: `${theme.space['4']} ${theme.space['6']}`,
    borderTop: `1px solid ${theme.colors.border}`,
    gap: theme.space['2'],
  };

  return (
    <div
      className="ui-modal-overlay"
      style={overlayStyle}
      onClick={handleOverlayClick}
      role="dialog"
      aria-modal="true"
      aria-labelledby={title ? 'modal-title' : undefined}
    >
      <div className="ui-modal" ref={modalRef} style={modalStyle}>
        {title && (
          <div className="ui-modal-header" style={headerStyle}>
            <h3 id="modal-title" className="ui-modal-title" style={titleStyle}>
              {title}
            </h3>
            <button
              className="ui-modal-close"
              style={closeButtonStyle}
              onClick={onClose}
              aria-label="Close"
            >
              ×
            </button>
          </div>
        )}
        
        <div className="ui-modal-body" style={bodyStyle}>
          {children}
        </div>
        
        {footer && (
          <div className="ui-modal-footer" style={footerStyle}>
            {footer}
          </div>
        )}
      </div>
    </div>
  );
};

export default Modal;