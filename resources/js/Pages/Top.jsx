import { Head } from '@inertiajs/react'
import { SideMenu } from '../Components/SideMenu'
import { HiChat } from 'react-icons/hi'
import { LogoutButton } from '../Components/LogoutButton'
export default function Top({ threads }) {
    return (
        <>
            <Head title="Top" />
            <div className="flex min-h-screen bg-gray-600">
                <div className="w-64 bg-green-500">
                    <div className="p-4">
                        <h1 className="text-2xl text-white mb-6 flex items-center gap-2">
                            <HiChat className="text-2xl" />
                            MyEnglishApp
                        </h1>
                    </div>
                    <SideMenu threads={threads} />
                </div>
                <div className="flex-1 p-8">
                <div className="flex justify-end mb-6">
                        <LogoutButton />
                    </div>
                    <h2 className="text-2xl text-white mb-6">英会話学習記録</h2>
                    <div className="grid grid-cols-11 gap-4">
                        {[...Array(77)].map((_, index) => (
                            <div key={index} className="w-full aspect-square bg-white rounded"></div>
                        ))}
                    </div>
                </div>
            </div>
        </>
    )
}
