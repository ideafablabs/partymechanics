import { useRouter } from 'next/router';
import Link from 'next/link';
import { getURL, classNames } from '../utils/helpers';

const tabs = [
  {
    name: 'Connections',
    href: '/connections',
  },
  {
    name: 'Events',
    href: '/events',
  },
];

export default function NavTabs () {
  const router = useRouter();

  return (
    <nav className="isolate flex divide-x divide-gray-200 rounded-lg shadow" aria-label="Tabs">
      {tabs.map((tab, tabIdx) => (
        <Link key={tab.name} href={tab.href} aria-current={router.pathname === tab.href ? 'page' : undefined}
        className={classNames(
          router.pathname === tab.href ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700',
          tabIdx === 0 ? 'rounded-l-lg' : '',
          tabIdx === tabs.length - 1 ? 'rounded-r-lg' : '',
          'group relative min-w-0 flex-1 overflow-hidden bg-white py-4 px-6 text-sm font-medium text-center hover:bg-gray-50 focus:z-10'
        )}>
            <span>{tab.name}</span>
            <span
              aria-hidden="true"
              className={classNames(
                router.pathname === tab.href ? 'bg-sky-500' : 'bg-transparent',
                'absolute inset-x-0 bottom-0 h-0.5'
              )}
            />
        </Link>
      ))}
    </nav>
  );
};