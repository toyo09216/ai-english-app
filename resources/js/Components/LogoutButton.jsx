import { router } from '@inertiajs/react';

export function LogoutButton() {
    const handleLogout = () => {
        router.post(route('logout'));
    };

    return (
        <button
            onClick={handleLogout}
            className="bg-gray-300 px-6 py-2 rounded hover:bg-gray-400"
        >
            ログアウト
        </button>
    );
}

export default LogoutButton;
