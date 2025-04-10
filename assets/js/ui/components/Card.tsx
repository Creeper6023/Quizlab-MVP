import React from 'react';
import theme from '../styles/theme';

export type CardVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info';

export interface CardProps {
  children: React.ReactNode;
  title?: string;
  subtitle?: string;
  footer?: React.ReactNode;
  className?: string;
  style?: React.CSSProperties;
  bordered?: boolean;
  shadowed?: boolean;
  padding?: keyof typeof theme.space | 'none';
  variant?: CardVariant;
}

export const Card: React.FC<CardProps> = ({
  children,
  title,
  subtitle,
  footer,
  className = '',
  style,
  bordered = true,
  shadowed = true,
  padding = '5',
  variant = 'default',
}) => {
  const getVariantStyles = (): React.CSSProperties => {
    switch (variant) {
      case 'primary':
        return {
          borderColor: theme.colors.primary,
          borderLeftWidth: '4px',
        };
      case 'success':
        return {
          borderColor: theme.colors.success,
          borderLeftWidth: '4px',
        };
      case 'warning':
        return {
          borderColor: theme.colors.warning,
          borderLeftWidth: '4px',
        };
      case 'danger':
        return {
          borderColor: theme.colors.danger,
          borderLeftWidth: '4px',
        };
      case 'info':
        return {
          borderColor: theme.colors.info,
          borderLeftWidth: '4px',
        };
      default:
        return {};
    }
  };

  const cardStyle: React.CSSProperties = {
    backgroundColor: theme.colors.background,
    borderRadius: theme.radii.lg,
    overflow: 'hidden',
    ...(bordered && { border: `1px solid ${theme.colors.border}` }),
    ...(shadowed && { boxShadow: theme.shadows.md }),
    ...getVariantStyles(),
    ...style,
  };

  const cardBodyStyle: React.CSSProperties = {
    padding: padding === 'none' ? '0' : theme.space[padding as keyof typeof theme.space],
  };

  const cardHeaderStyle: React.CSSProperties = {
    padding: padding === 'none' ? '0' : theme.space[padding as keyof typeof theme.space],
    borderBottom: bordered ? `1px solid ${theme.colors.border}` : 'none',
  };

  const cardFooterStyle: React.CSSProperties = {
    padding: padding === 'none' ? '0' : theme.space[padding as keyof typeof theme.space],
    borderTop: bordered ? `1px solid ${theme.colors.border}` : 'none',
  };

  const getTitleColor = (): string => {
    switch (variant) {
      case 'primary':
        return theme.colors.primary;
      case 'success':
        return theme.colors.success;
      case 'warning':
        return theme.colors.warning;
      case 'danger':
        return theme.colors.danger;
      case 'info':
        return theme.colors.info;
      default:
        return theme.colors.text;
    }
  };

  const titleStyle: React.CSSProperties = {
    fontSize: theme.fontSizes.xl,
    fontWeight: 'bold',
    margin: 0,
    color: getTitleColor(),
  };

  const subtitleStyle: React.CSSProperties = {
    fontSize: theme.fontSizes.md,
    color: theme.colors.muted,
    margin: subtitle ? `${theme.space['1']} 0 0 0` : 0,
  };

  return (
    <div className={`ui-card ui-card-${variant} ${className}`} style={cardStyle}>
      {(title || subtitle) && (
        <div className="ui-card-header" style={cardHeaderStyle}>
          {title && <h3 style={titleStyle}>{title}</h3>}
          {subtitle && <p style={subtitleStyle}>{subtitle}</p>}
        </div>
      )}
      <div className="ui-card-body" style={cardBodyStyle}>
        {children}
      </div>
      {footer && (
        <div className="ui-card-footer" style={cardFooterStyle}>
          {footer}
        </div>
      )}
    </div>
  );
};

export default Card;