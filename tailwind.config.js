/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/**/*.php",
    "./resources/views/**/*.blade.php",
    "./resources/views/components/**/*.blade.php", 
    "./resources/views/livewire/**/*.blade.php",
    "./resources/views/backoffice/**/*.blade.php",
    "./vendor/livewire/flux/stubs/**/*.blade.php",
    "./vendor/livewire/flux-pro/stubs/**/*.blade.php",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      colors: {
        'accent': 'var(--color-accent)',
        'accent-content': 'var(--color-accent-content)', 
        'accent-foreground': 'var(--color-accent-foreground)',
      }
    },
  },
  plugins: [],
  safelist: [
    // Garantir que classes dinâmicas sejam incluídas
    'bg-yellow-100', 'bg-yellow-800', 'bg-yellow-900', 'text-yellow-200', 'text-yellow-600', 'text-yellow-400',
    'bg-blue-100', 'bg-blue-800', 'bg-blue-900', 'text-blue-200', 'text-blue-600', 'text-blue-400',
    'bg-green-100', 'bg-green-800', 'bg-green-900', 'text-green-200', 'text-green-600', 'text-green-400',
    'bg-red-100', 'bg-red-800', 'bg-red-900', 'text-red-200', 'text-red-600', 'text-red-400',
    'bg-purple-100', 'bg-purple-800', 'bg-purple-900', 'text-purple-200', 'text-purple-600', 'text-purple-400',
    'bg-gray-100', 'bg-gray-800', 'bg-gray-900', 'text-gray-200', 'text-gray-600', 'text-gray-400',
    'hover:bg-blue-700', 'hover:bg-green-700', 'hover:bg-yellow-700', 'hover:bg-red-700', 'hover:bg-purple-700',
    'dark:bg-yellow-900', 'dark:bg-blue-900', 'dark:bg-green-900', 'dark:bg-red-900', 'dark:bg-purple-900',
    'dark:text-yellow-200', 'dark:text-blue-200', 'dark:text-green-200', 'dark:text-red-200', 'dark:text-purple-200',
    'bg-gradient-to-r', 'from-blue-600', 'to-purple-600', 'from-blue-700', 'to-purple-700',
    'hover:scale-[1.02]', 'transform', 'transition-all', 'duration-200',
    'shadow-lg', 'hover:shadow-xl',
    // Classes de grid e layout
    'grid-cols-1', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4', 'sm:grid-cols-2', 'lg:grid-cols-3', 'xl:grid-cols-4', 'md:grid-cols-3',
    // Classes de espaçamento
    'gap-1', 'gap-2', 'gap-3', 'gap-4', 'gap-5', 'gap-6', 'gap-7', 'gap-8',
    // Classes de largura e altura
    'w-4', 'w-5', 'w-6', 'w-8', 'w-10', 'w-12', 'w-16', 'w-20', 'w-24',
    'h-4', 'h-5', 'h-6', 'h-8', 'h-10', 'h-12', 'h-16', 'h-20', 'h-24', 'h-32',
    // Classes de padding e margin
    'p-1', 'p-2', 'p-3', 'p-4', 'p-5', 'p-6', 'p-7', 'p-8',
    'm-1', 'm-2', 'm-3', 'm-4', 'm-5', 'm-6', 'm-7', 'm-8',
    'px-1', 'px-2', 'px-3', 'px-4', 'px-5', 'px-6', 'px-7', 'px-8',
    'py-1', 'py-2', 'py-3', 'py-4', 'py-5', 'py-6', 'py-7', 'py-8',
  ]
}