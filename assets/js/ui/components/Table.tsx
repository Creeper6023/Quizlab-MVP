import React from 'react';
import theme from '../styles/theme';

export interface TableColumn<T> {
  header: string;
  accessor: keyof T | ((item: T) => React.ReactNode);
  width?: string;
}

export interface TableProps<T> {
  data: T[];
  columns: TableColumn<T>[];
  onRowClick?: (item: T) => void;
  isLoading?: boolean;
  emptyMessage?: string;
  sortable?: boolean;
  className?: string;
  style?: React.CSSProperties;
  rowClassName?: (item: T) => string;
}

export function Table<T extends Record<string, any>>({
  data,
  columns,
  onRowClick,
  isLoading = false,
  emptyMessage = 'No data available',
  sortable = false,
  className = '',
  style,
  rowClassName,
}: TableProps<T>) {
  const [sortConfig, setSortConfig] = React.useState<{
    key: keyof T | null;
    direction: 'asc' | 'desc';
  }>({
    key: null,
    direction: 'asc',
  });

  const handleSort = (key: keyof T) => {
    if (!sortable) return;
    
    setSortConfig((prevSortConfig) => ({
      key,
      direction:
        prevSortConfig.key === key && prevSortConfig.direction === 'asc'
          ? 'desc'
          : 'asc',
    }));
  };

  const sortedData = React.useMemo(() => {
    if (!sortConfig.key) return data;
    
    return [...data].sort((a, b) => {
      const aValue = a[sortConfig.key!];
      const bValue = b[sortConfig.key!];
      
      if (aValue === bValue) return 0;
      
      if (sortConfig.direction === 'asc') {
        return aValue < bValue ? -1 : 1;
      } else {
        return aValue > bValue ? -1 : 1;
      }
    });
  }, [data, sortConfig]);


  const tableContainerStyle: React.CSSProperties = {
    width: '100%',
    overflowX: 'auto',
    ...style,
  };

  const tableStyle: React.CSSProperties = {
    width: '100%',
    borderCollapse: 'collapse',
    fontFamily: theme.fonts.body,
    fontSize: theme.fontSizes.sm,
  };

  const tableHeaderStyle: React.CSSProperties = {
    borderBottom: `1px solid ${theme.colors.border}`,
    backgroundColor: theme.colors.light,
    fontWeight: 'bold',
    color: theme.colors.text,
    textAlign: 'left',
    padding: theme.space['3'],
    position: 'sticky',
    top: 0,
  };

  const sortableHeaderStyle: React.CSSProperties = {
    cursor: 'pointer',
    userSelect: 'none',
  };

  const tableCellStyle: React.CSSProperties = {
    padding: theme.space['3'],
    borderBottom: `1px solid ${theme.colors.border}`,
    color: theme.colors.text,
  };

  const tableRowStyle: React.CSSProperties = {
    transition: theme.transitions.fast,
  };

  const clickableRowStyle: React.CSSProperties = {
    cursor: 'pointer',
  };

  const loadingOverlayStyle: React.CSSProperties = {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(255, 255, 255, 0.7)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
  };

  const emptyStateStyle: React.CSSProperties = {
    padding: theme.space['6'],
    textAlign: 'center',
    color: theme.colors.muted,
  };

  return (
    <div style={{ position: 'relative', ...tableContainerStyle }} className={`ui-table-container ${className}`}>
      <table style={tableStyle} className="ui-table">
        <thead>
          <tr>
            {columns.map((column, index) => (
              <th
                key={index}
                style={{
                  ...tableHeaderStyle,
                  ...(sortable && typeof column.accessor === 'string'
                    ? sortableHeaderStyle
                    : {}),
                  width: column.width,
                }}
                onClick={() => {
                  if (sortable && typeof column.accessor === 'string') {
                    handleSort(column.accessor);
                  }
                }}
                className={`ui-table-header ${
                  sortable && typeof column.accessor === 'string'
                    ? 'ui-table-header-sortable'
                    : ''
                }`}
              >
                {column.header}
                {sortable &&
                  typeof column.accessor === 'string' &&
                  sortConfig.key === column.accessor && (
                    <span style={{ marginLeft: theme.space['1'] }}>
                      {sortConfig.direction === 'asc' ? '↑' : '↓'}
                    </span>
                  )}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {sortedData.length > 0 ? (
            sortedData.map((item, rowIndex) => (
              <tr
                key={rowIndex}
                style={{
                  ...tableRowStyle,
                  ...(onRowClick ? clickableRowStyle : {}),
                }}
                onClick={() => onRowClick && onRowClick(item)}
                className={`ui-table-row ${
                  onRowClick ? 'ui-table-row-clickable' : ''
                } ${rowClassName ? rowClassName(item) : ''}`}
              >
                {columns.map((column, cellIndex) => (
                  <td key={cellIndex} style={tableCellStyle} className="ui-table-cell">
                    {typeof column.accessor === 'function'
                      ? column.accessor(item)
                      : item[column.accessor]}
                  </td>
                ))}
              </tr>
            ))
          ) : (
            <tr>
              <td
                colSpan={columns.length}
                style={emptyStateStyle}
                className="ui-table-empty"
              >
                {emptyMessage}
              </td>
            </tr>
          )}
        </tbody>
      </table>

      {isLoading && (
        <div style={loadingOverlayStyle} className="ui-table-loading">
          <div className="ui-table-spinner">Loading...</div>
        </div>
      )}
    </div>
  );
}

export default Table;