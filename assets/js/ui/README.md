# ShadowUI Component Library

## Overview
This is a lightweight, modular UI component library designed for modern web applications.

## Components

### Button

```tsx
<Button 
  variant="primary" // primary, secondary, success, danger, warning, info, light, dark, link
  size="md" // sm, md, lg
  isLoading={false}
  leftIcon={<i className="fas fa-check" />}
  rightIcon={<i className="fas fa-arrow-right" />}
  fullWidth={false}
  onClick={() => console.log('Button clicked')}
>
  Button Text
</Button>
```

### Card

```tsx
<Card
  title="Card Title"
  subtitle="Optional subtitle"
  footer={<div>Card Footer</div>}
  bordered={true}
  shadowed={true}
  padding="5" // Any value from theme.space or 'none'
>
  Card content goes here
</Card>
```

### Input

```tsx
<Input
  label="Username"
  placeholder="Enter your username"
  helperText="This will be your display name"
  error="Username is required" // Displays as error message
  leftIcon={<i className="fas fa-user" />}
  rightIcon={<i className="fas fa-check" />}
  fullWidth={true}
  size="md" // sm, md, lg
  name="username"
  onChange={handleChange}
/>
```

### Modal

```tsx
const [isOpen, setIsOpen] = useState(false);

// In your component:
<Button onClick={() => setIsOpen(true)}>Open Modal</Button>

<Modal
  isOpen={isOpen}
  onClose={() => setIsOpen(false)}
  title="Modal Title"
  size="md" // sm, md, lg, xl, full
  footer={
    <div>
      <Button variant="secondary" onClick={() => setIsOpen(false)}>Cancel</Button>
      <Button variant="primary" onClick={handleSave}>Save</Button>
    </div>
  }
>
  Modal content goes here
</Modal>
```

### Toast

```tsx
// In your component:
import { useToast } from '../ui';

function MyComponent() {
  const { showToast } = useToast();

  const handleAction = () => {
    // Do something
    showToast({
      message: "Action completed successfully!",
      variant: "success", // info, success, warning, error
      duration: 3000, // ms
    });
  };

  return <Button onClick={handleAction}>Show Toast</Button>;
}

// Make sure you have the ToastProvider wrapping your app:
// <ToastProvider>
//   <App />
// </ToastProvider>
```

### Table

```tsx
// Define your data type
interface User {
  id: number;
  name: string;
  email: string;
  role: string;
}

// Define columns
const columns: TableColumn<User>[] = [
  { header: 'ID', accessor: 'id', width: '50px' },
  { header: 'Name', accessor: 'name' },
  { header: 'Email', accessor: 'email' },
  { 
    header: 'Actions', 
    accessor: (user) => (
      <Button variant="danger" size="sm" onClick={() => handleDelete(user.id)}>
        Delete
      </Button>
    ),
    width: '100px'
  }
];

// Use the table
<Table
  data={users}
  columns={columns}
  onRowClick={(user) => handleRowClick(user)}
  isLoading={loading}
  emptyMessage="No users found"
  sortable={true}
/>
```

### Loader

```tsx
// Full screen loader
<Loader fullScreen text="Loading application..." />

// Inline loader
<div>
  <h2>Content is loading</h2>
  <Loader size="md" color="#4F46E5" />
</div>
```

### useForm Hook

```tsx
const { 
  values, 
  errors, 
  touched,
  isSubmitting, 
  handleChange, 
  handleBlur, 
  handleSubmit,
  resetForm,
  setFieldValue 
} = useForm({
  initialValues: {
    username: '',
    email: '',
    password: ''
  },
  validate: (values) => {
    const errors: Record<string, string> = {};
    if (!values.username) {
      errors.username = 'Username is required';
    }
    return errors;
  },
  onSubmit: async (values) => {
    // Submit form data
    await api.createUser(values);
  }
});

// In your form:
<form onSubmit={handleSubmit}>
  <Input
    name="username"
    value={values.username}
    onChange={handleChange}
    onBlur={handleBlur}
    error={touched.username && errors.username}
  />
  <Button type="submit" isLoading={isSubmitting}>Submit</Button>
</form>
```

## Theme Customization

The component library uses a theme object that can be customized. Import and extend the theme:

```tsx
import { theme } from '../ui';

// Extend or modify the theme
const customTheme = {
  ...theme,
  colors: {
    ...theme.colors,
    primary: '#8B5CF6', // Change primary color to purple
  }
};