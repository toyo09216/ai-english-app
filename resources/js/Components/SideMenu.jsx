"use client";

import { HiArrowSmRight, HiChartPie, HiInbox, HiShoppingBag, HiTable, HiUser, HiViewBoards, HiPlus, HiChat } from "react-icons/hi";
import { router } from '@inertiajs/react';

export function SideMenu({ threads = [] }) {
    const handleCreateThread = () => {
        router.post(route('thread.store'));
    };

    return (
        <div className="text-white">
            <div className="px-4 py-2">
                <button
                    onClick={handleCreateThread}
                    className="flex items-center space-x-2 text-lg w-full bg-green-600 hover:bg-green-700 px-4 py-2 rounded"
                >
                    <HiPlus className="text-xl" />
                    <span>新規スレッド作成</span>
                </button>
            </div>
            <nav className="mt-4">
                {threads.map((thread) => (
                    <a
                        key={thread.id}
                        href={`/thread/${thread.id}`}
                        className="flex items-center space-x-2 px-4 py-2 hover:bg-green-600"
                    >
                        <HiChat className="text-xl" />
                        <span>{thread.title}</span>
                    </a>
                ))}
            </nav>
        </div>
    );
}

export default SideMenu;
