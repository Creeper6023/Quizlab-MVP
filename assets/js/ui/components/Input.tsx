import React from 'react';
import theme from '../styles/theme';

export interface InputProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'size'> {
  label?: string;
  helperText?: string;
  error?: string;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
  fullWidth?: boolean;
  size?: 'sm' | 'md' | 'lg';
}

export const Input: React.FC<InputProps> = ({
  label,
  helperText,
  error,
  leftIcon,
  rightIcon,
  fullWidth = false,
  size = 'md',
  className = '',
  id,
  ...props
}) => {
  // Generate a unique ID for accessibility if not provided
  const inputId = id || `input-${Math.random().toString(36).substring(2, 9)}`;

  // Base styles
  const containerStyle: React.CSSProperties = {
    display: 'block',
    marginBottom: theme.space['4'],
    width: fullWidth ? '100%' : 'auto',
  };

  const labelStyle: React.CSSProperties = {
    display: 'block',
    marginBottom: theme.space['1'],
    fontSize: theme.fontSizes.sm,
    fontWeight: 'medium',
    color: error ? theme.colors.danger : theme.colors.text,
  };

  // Determine input size
  const inputSizeStyles = {
    sm: { height: theme.space['8'], padding: `0 ${theme.space['2']}`, fontSize: theme.fontSizes.xs },
    md: { height: theme.space['10'], padding: `0 ${theme.space['3']}`, fontSize: theme.fontSizes.sm },
    lg: { height: theme.space['12'], padding: `0 ${theme.space['4']}`, fontSize: theme.fontSizes.md },
  };

  const inputWrapperStyle: React.CSSProperties = {
    position: 'relative',
    display: 'flex',
    alignItems: 'center',
  };

  const iconStyle: React.CSSProperties = {
    position: 'absolute',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    height: '100%',
    width: theme.space['10'],
    color: theme.colors.muted,
  };

  const leftIconStyle: React.CSSProperties = {
    ...iconStyle,
    left: 0,
  };

  const rightIconStyle: React.CSSProperties = {
    ...iconStyle,
    right: 0,
  };

  const inputStyle: React.CSSProperties = {
    display: 'block',
    width: '100%',
    border: `1px solid ${error ? theme.colors.danger : theme.colors.border}`,
    borderRadius: theme.radii.md,
    backgroundColor: props.disabled ? theme.colors.light : theme.colors.background,
    color: theme.colors.text,
    ...inputSizeStyles[size],
    ...(leftIcon && { paddingLeft: theme.space['10'] }),
    ...(rightIcon && { paddingRight: theme.space['10'] }),
    outline: 'none',
    transition: theme.transitions.normal,
  };

  const helperTextStyle: React.CSSProperties = {
    display: 'block',
    marginTop: theme.space['1'],
    fontSize: theme.fontSizes.xs,
    color: error ? theme.colors.danger : theme.colors.muted,
  };

  return (
    <div style={containerStyle} className={`ui-input-container ${className}`}>
      {label && (
        <label htmlFor={inputId} style={labelStyle} className="ui-input-label">
          {label}
        </label>
      )}
      <div style={inputWrapperStyle} className="ui-input-wrapper">
        {leftIcon && <div style={leftIconStyle} className="ui-input-icon-left">{leftIcon}</div>}
        <input
          id={inputId}
          style={inputStyle}
          className={`ui-input ui-input-${size} ${error ? 'ui-input-error' : ''}`}
          aria-invalid={!!error}
          aria-describedby={error || helperText ? `${inputId}-help` : undefined}
          {...props}
        />
        {rightIcon && <div style={rightIconStyle} className="ui-input-icon-right">{rightIcon}</div>}
      </div>
      {(helperText || error) && (
        <div
          id={`${inputId}-help`}
          style={helperTextStyle}
          className={`ui-input-helper-text ${error ? 'ui-input-error-text' : ''}`}
        >
          {error || helperText}
        </div>
      )}
    </div>
  );
};

export default Input;