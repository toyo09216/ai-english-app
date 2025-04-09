import { Head } from '@inertiajs/react'
import { SideMenu } from '../../Components/SideMenu'
import { HiChat } from 'react-icons/hi'
import { LogoutButton } from '../../Components/LogoutButton'
import { HiVolumeUp } from 'react-icons/hi'
import { useState, useRef, useEffect } from 'react'
import axios from 'axios'
import { HiMicrophone } from 'react-icons/hi'

export default function Show({ thread, messages: initialMessages, threads, threadId }) {
    const [messages, setMessages] = useState(initialMessages);
    const [isRecording, setIsRecording] = useState(false)
    const [isLoading, setIsLoading] = useState(false)
    const mediaRecorderRef = useRef(null)
    const audioChunksRef = useRef([])
    const currentAudioRef = useRef(null)
    const [showJapanese, setShowJapanese] = useState({});

    // initialMessagesが変更されたときにmessagesを更新
    useEffect(() => {
        setMessages(initialMessages);
    }, [initialMessages]);

    // 最新のメッセージの音声を自動再生する
    useEffect(() => {
        // messagesが更新され、最後のメッセージがAIからの返答（sender !== 1）の場合に音声を再生
        const latestMessage = messages[messages.length - 1];
        if (
            messages.length > 0 &&
            latestMessage &&
            latestMessage.sender !== 1 && // AIからのメッセージ
            latestMessage.thread_id === threadId &&
            latestMessage.audio_file_path
        ) {
            const audio = new Audio(`/storage/${latestMessage.audio_file_path}`);
            audio.play().catch(error => {
                console.error('音声の自動再生に失敗しました:', error);
            });
        }
    }, [messages, threadId]);

    const handleRecording = async () => {
        if (!isRecording) {
            // 録音開始
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
                const mediaRecorder = new MediaRecorder(stream)
                mediaRecorderRef.current = mediaRecorder
                audioChunksRef.current = []

                mediaRecorder.ondataavailable = (event) => {
                    audioChunksRef.current.push(event.data)
                }

                mediaRecorder.onstop = async () => {
                    const audioBlob = new Blob(audioChunksRef.current, { type: 'audio/wav' })
                    const formData = new FormData()
                    formData.append('audio', audioBlob)
                    formData.append('thread_id', threadId)
                    formData.append('sender', 1)

                    try {
                        const response = await axios.post(`/thread/${threadId}/message`, formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            }
                        });

                        // レスポンスの確認
                        if (response.data.status === 200) {
                            window.location.reload()
                        } else {
                            console.error('Server response:', response.data);
                            alert(`エラーが発生しました: ${response.data.message}`);
                        }
                    } catch (error) {
                        console.error('Error details:', error.response?.data || error.message);
                        alert(`音声データの送信に失敗しました: ${error.response?.data?.message || error.message}`);
                    } finally {
                        setIsLoading(false)
                    }
                }

                mediaRecorder.start()
                setIsRecording(true)
            } catch (error) {
                console.error('Error accessing microphone:', error)
            }
        } else {
            // 録音停止
            setIsLoading(true)
            mediaRecorderRef.current.stop()
            mediaRecorderRef.current.stream.getTracks().forEach(track => track.stop())
            setIsRecording(false)
        }
    }

    return (
        <>
            <Head title="Show" />
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
                    <div className="space-y-6">
                        {messages.map((message) => (
                            message.sender === 1 ? (
                                // User message
                                <div key={message.id} className="flex justify-end gap-2">
                                    <div className="bg-gray-200 rounded-lg p-4 max-w-xl">
                                        {message.message_en}
                                    </div>
                                    <div className="bg-gray-200 rounded-full px-4 py-2 self-start">
                                        You
                                    </div>
                                </div>
                            ) : (
                                // AI message
                                <div key={message.id} className="flex gap-2">
                                    <div className="bg-gray-200 rounded-full px-4 py-2 self-start">
                                        AI
                                    </div>
                                    <div className="bg-gray-200 rounded-lg p-4 max-w-xl">
                                        {showJapanese[message.id] && message.message_ja
                                            ? message.message_ja
                                            : message.message_en}
                                    </div>
                                    <div className="flex gap-2">
                                        <button
                                            className="bg-gray-200 rounded-full p-2"
                                            onClick={() => {
                                                // 現在再生中の音声があれば停止
                                                if (currentAudioRef.current) {
                                                    currentAudioRef.current.pause();
                                                    currentAudioRef.current = null;
                                                    return;
                                                }

                                                // 新しい音声を再生
                                                if (message.audio_file_path) {
                                                    const audio = new Audio(`/storage/${message.audio_file_path}`);
                                                    audio.play().catch(error => {
                                                        console.error('音声の再生に失敗しました:', error);
                                                    });

                                                    // 再生中の音声を参照として保存
                                                    currentAudioRef.current = audio;

                                                    // 再生が終了したら参照をクリア
                                                    audio.onended = () => {
                                                        currentAudioRef.current = null;
                                                    };
                                                }
                                            }}
                                        >
                                            <HiVolumeUp className="text-xl" />
                                        </button>
                                        <button
                                            className="bg-gray-200 rounded-full px-3 py-2"
                                            onClick={async () => {
                                                // 現在の表示状態を反転
                                                const newShowJapanese = !showJapanese[message.id];
                                                setShowJapanese(prev => ({
                                                    ...prev,
                                                    [message.id]: newShowJapanese
                                                }));

                                                // 日本語訳が必要で、まだ取得していない場合のみAPIを呼び出す
                                                if (newShowJapanese && !message.message_ja) {
                                                    try {
                                                        const response = await axios.post(
                                                            `/thread/${threadId}/message/${message.id}/translate`
                                                        );

                                                        setMessages(prevMessages =>
                                                            prevMessages.map(msg =>
                                                                msg.id === message.id
                                                                    ? { ...msg, message_ja: response.data.message_ja }
                                                                    : msg
                                                            )
                                                        );
                                                    } catch (error) {
                                                        console.error('Translation error:', error);
                                                        alert('翻訳に失敗しました');
                                                        // エラーの場合は表示状態を元に戻す
                                                        setShowJapanese(prev => ({
                                                            ...prev,
                                                            [message.id]: !newShowJapanese
                                                        }));
                                                    }
                                                }
                                            }}
                                        >
                                            Aあ
                                        </button>
                                    </div>
                                </div>
                            )
                        ))}
                    </div>

                    {/* Input area */}
                    <div className="fixed bottom-8 right-8 left-72">
                        <div className="relative">
                            <button
                                className={`absolute right-4 bottom-4 rounded-full p-4 ${
                                    isRecording ? 'bg-red-500' : 'bg-purple-300'
                                }`}
                                onClick={handleRecording}
                            >
                                <HiMicrophone className="text-xl" />
                            </button>
                        </div>
                    </div>
                </div>

                {/* Loading Overlay */}
                {isLoading && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div className="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
                    </div>
                )}
            </div>
        </>
    )
}
