import React from 'react';
import theme from '../styles/theme';

export type ButtonVariant = 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'light' | 'dark' | 'link';
export type ButtonSize = 'sm' | 'md' | 'lg';

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  isLoading?: boolean;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
  fullWidth?: boolean;
}

export const Button: React.FC<ButtonProps> = ({
  children,
  variant = 'primary',
  size = 'md',
  isLoading = false,
  leftIcon,
  rightIcon,
  fullWidth = false,
  disabled,
  className = '',
  ...props
}) => {

  const baseStyle = {
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontWeight: 'medium',
    borderRadius: theme.radii.md,
    transition: theme.transitions.normal,
    cursor: disabled || isLoading ? 'not-allowed' : 'pointer',
    width: fullWidth ? '100%' : 'auto',
    opacity: disabled ? 0.6 : 1,
  };


  const sizeStyles = {
    sm: {
      fontSize: theme.fontSizes.xs,
      padding: `${theme.space['1']} ${theme.space['2']}`,
      height: theme.space['8'],
    },
    md: {
      fontSize: theme.fontSizes.sm,
      padding: `${theme.space['2']} ${theme.space['4']}`,
      height: theme.space['10'],
    },
    lg: {
      fontSize: theme.fontSizes.md,
      padding: `${theme.space['3']} ${theme.space['6']}`,
      height: theme.space['12'],
    },
  };


  const variantStyles = {
    primary: {
      backgroundColor: theme.colors.primary,
      color: 'white',
      border: 'none',
    },
    secondary: {
      backgroundColor: theme.colors.secondary,
      color: 'white',
      border: 'none',
    },
    success: {
      backgroundColor: theme.colors.success,
      color: 'white',
      border: 'none',
    },
    danger: {
      backgroundColor: theme.colors.danger,
      color: 'white',
      border: 'none',
    },
    warning: {
      backgroundColor: theme.colors.warning,
      color: 'white',
      border: 'none',
    },
    info: {
      backgroundColor: theme.colors.info,
      color: 'white',
      border: 'none',
    },
    light: {
      backgroundColor: theme.colors.light,
      color: theme.colors.dark,
      border: `1px solid ${theme.colors.border}`,
    },
    dark: {
      backgroundColor: theme.colors.dark,
      color: 'white',
      border: 'none',
    },
    link: {
      backgroundColor: 'transparent',
      color: theme.colors.primary,
      border: 'none',
      padding: 0,
      height: 'auto',
    },
  };


  const buttonClasses = `ui-button ui-button-${variant} ui-button-${size} ${fullWidth ? 'ui-button-full-width' : ''} ${className}`;


  const btnStyle = {
    ...baseStyle,
    ...sizeStyles[size],
    backgroundColor: variantStyles[variant].backgroundColor,
    color: variantStyles[variant].color,
    border: variantStyles[variant].border,
  };

  return (
    <button
      className={buttonClasses}
      disabled={disabled || isLoading}
      style={btnStyle}
      {...props}
    >
      {isLoading && (
        <span className="spinner" style={{ marginRight: theme.space['2'] }}>
          ⟳
        </span>
      )}
      {leftIcon && <span style={{ marginRight: theme.space['2'] }}>{leftIcon}</span>}
      {children}
      {rightIcon && <span style={{ marginLeft: theme.space['2'] }}>{rightIcon}</span>}
    </button>
  );
};

export default Button;