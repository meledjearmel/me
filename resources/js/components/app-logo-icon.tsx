import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 56 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 40 22 0h4v12.7h-4L7 40z" />
            <path d="M9 26h16v6H9z" />
            <path d="M22 0h6v40h-6z" />
            <path d="M22 0h6l11 16.8L50 0h6L39 26z" />
            <path d="M50 0h6v40h-6z" />
        </svg>
    );
}
