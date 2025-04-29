import React from 'react';
import theme from '../styles/theme';

export interface LoaderProps {
  size?: 'sm' | 'md' | 'lg';
  color?: string;
  fullScreen?: boolean;
  text?: string;
}

export const Loader: React.FC<LoaderProps> = ({
  size = 'md',
  color = theme.colors.primary,
  fullScreen = false,
  text,
}) => {

  const sizes = {
    sm: { size: '16px', border: '2px' },
    md: { size: '24px', border: '3px' },
    lg: { size: '48px', border: '4px' },
  };


  const spinnerStyle: React.CSSProperties = {
    display: 'inline-block',
    width: sizes[size].size,
    height: sizes[size].size,
    border: `${sizes[size].border} solid rgba(0, 0, 0, 0.1)`,
    borderLeftColor: color,
    borderRadius: '50%',
    animation: 'spin 1s linear infinite',
  };


  const containerStyle: React.CSSProperties = fullScreen
    ? {
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'rgba(255, 255, 255, 0.8)',
        zIndex: 9999,
      }
    : {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
      };


  const textStyle: React.CSSProperties = {
    marginTop: theme.space['2'],
    color: theme.colors.text,
    fontSize: size === 'sm' ? theme.fontSizes.xs : size === 'md' ? theme.fontSizes.sm : theme.fontSizes.md,
  };



  React.useEffect(() => {

    const styleId = 'loader-keyframes';
    if (!document.getElementById(styleId)) {
      const styleEl = document.createElement('style');
      styleEl.id = styleId;
      styleEl.innerHTML = `
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      `;
      document.head.appendChild(styleEl);
    }


    return () => {


      if (document.getElementsByClassName('ui-loader').length <= 1) {
        const styleEl = document.getElementById(styleId);
        if (styleEl) {
          styleEl.remove();
        }
      }
    };
  }, []);

  return (
    <div style={containerStyle} className="ui-loader">
      <div style={spinnerStyle} className="ui-loader-spinner" />
      {text && <div style={textStyle} className="ui-loader-text">{text}</div>}
    </div>
  );
};

export default Loader;