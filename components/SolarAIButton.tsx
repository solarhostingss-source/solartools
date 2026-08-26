import React, { useState, useCallback, useEffect, useRef } from 'react';
import ReactDOM from 'react-dom';
import http from '@/api/http';

/**
 * ╔═══════════════════════════════════════════════════╗
 * ║  SolarTools Global Frontend Component             ║
 * ║  Renders SolarAI Button in a React Portal         ║
 * ╚═══════════════════════════════════════════════════╝
 */

interface AnalysisResponse {
    success: boolean;
    analysis?: string;
    error?: string;
}

interface Message {
    role: 'user' | 'model';
    text: string;
}

const AnalysisModal: React.FC<{
    isOpen: boolean;
    onClose: () => void;
    messages: Message[];
    isLoading: boolean;
    error: string | null;
    inputValue: string;
    setInputValue: (val: string) => void;
    onSend: () => void;
}> = ({ isOpen, onClose, messages, isLoading, error, inputValue, setInputValue, onSend }) => {
    const messagesEndRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, isLoading]);

    if (!isOpen) return null;

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            onSend();
        }
    };

    return ReactDOM.createPortal(
        <div style={{
            position: 'fixed',
            top: 0, left: 0, right: 0, bottom: 0,
            backgroundColor: 'rgba(0, 0, 0, 0.75)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            zIndex: 99999999, // Super max z-index
            padding: '20px',
        }}>
            <div style={{
                background: 'linear-gradient(135deg, #1a1d23 0%, #0d1117 100%)',
                borderRadius: '16px',
                border: '1px solid rgba(0, 212, 170, 0.2)',
                maxWidth: '700px', width: '100%', height: '80vh',
                display: 'flex', flexDirection: 'column',
                boxShadow: '0 25px 60px rgba(0, 0, 0, 0.5), 0 0 40px rgba(0, 212, 170, 0.1)',
            }}>
                <div style={{
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    padding: '20px 24px', borderBottom: '1px solid rgba(255, 255, 255, 0.08)',
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <div style={{
                            width: '36px', height: '36px', borderRadius: '10px',
                            background: 'linear-gradient(135deg, #00D4AA, #00B894)',
                            display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '18px',
                        }}>☀️</div>
                        <div>
                            <h2 style={{ margin: 0, fontSize: '18px', fontWeight: 700, color: '#ffffff' }}>Solar AI - Asistente</h2>
                            <p style={{ margin: 0, fontSize: '12px', color: 'rgba(255,255,255,0.5)' }}>Powered by Solar AI</p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        style={{
                            background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.1)',
                            borderRadius: '8px', color: '#ffffff', width: '32px', height: '32px',
                            cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '16px',
                        }}
                    >✕</button>
                </div>

                <div style={{ flex: 1, overflowY: 'auto', padding: '24px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
                    {messages.length === 0 && !isLoading && (
                        <div style={{ textAlign: 'center', color: 'rgba(255,255,255,0.5)', marginTop: '40px' }}>
                            <div style={{ fontSize: '40px', marginBottom: '16px' }}>☀️</div>
                            <h3 style={{ margin: '0 0 8px 0', color: '#fff' }}>¡Hola! Soy Solar AI.</h3>
                            <p style={{ margin: 0 }}>¿En qué puedo ayudarte hoy?</p>
                        </div>
                    )}

                    {messages.map((msg, idx) => (
                        <div key={idx} style={{
                            alignSelf: msg.role === 'user' ? 'flex-end' : 'flex-start',
                            background: msg.role === 'user' ? 'linear-gradient(135deg, #00D4AA, #00B894)' : 'rgba(255,255,255,0.05)',
                            color: msg.role === 'user' ? '#0d1117' : 'rgba(255,255,255,0.85)',
                            padding: '12px 16px', borderRadius: '12px', maxWidth: '85%',
                            border: msg.role === 'model' ? '1px solid rgba(255,255,255,0.1)' : 'none',
                        }}>
                            {msg.role === 'user' ? (
                                <div style={{ whiteSpace: 'pre-wrap', fontSize: '14px' }}>{msg.text}</div>
                            ) : (
                                <div
                                    style={{ fontSize: '14px', lineHeight: '1.7', whiteSpace: 'pre-wrap' }}
                                    dangerouslySetInnerHTML={{
                                        __html: msg.text
                                            .replace(/^### (.*$)/gm, '<h3 style="color:#00D4AA;margin:16px 0 8px;font-size:16px">$1</h3>')
                                            .replace(/^## (.*$)/gm, '<h2 style="color:#00D4AA;margin:20px 0 10px;font-size:18px">$1</h2>')
                                            .replace(/^# (.*$)/gm, '<h1 style="color:#00D4AA;margin:20px 0 10px;font-size:20px">$1</h1>')
                                            .replace(/\*\*(.*?)\*\*/g, '<strong style="color:#ffffff">$1</strong>')
                                            .replace(/`(.*?)`/g, '<code style="background:rgba(0,0,0,0.3);color:#00D4AA;padding:2px 6px;border-radius:4px;font-size:13px">$1</code>')
                                            .replace(/^- (.*$)/gm, '<li style="margin:4px 0;margin-left:16px">$1</li>')
                                            .replace(/\n/g, '<br/>'),
                                    }}
                                />
                            )}
                        </div>
                    ))}

                    {isLoading && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: '12px', alignSelf: 'flex-start', background: 'rgba(255,255,255,0.05)', padding: '12px 16px', borderRadius: '12px', border: '1px solid rgba(255,255,255,0.1)' }}>
                            <div style={{ width: '16px', height: '16px', border: '2px solid rgba(0, 212, 170, 0.2)', borderTopColor: '#00D4AA', borderRadius: '50%', animation: 'solarSpin 0.8s linear infinite' }} />
                            <span style={{ color: 'rgba(255,255,255,0.7)', fontSize: '13px' }}>Pensando...</span>
                            <style>{`@keyframes solarSpin { to { transform: rotate(360deg); } }`}</style>
                        </div>
                    )}

                    {error && (
                        <div style={{ background: 'rgba(255, 68, 68, 0.1)', border: '1px solid rgba(255, 68, 68, 0.2)', borderRadius: '10px', padding: '16px', color: '#FF6B6B', fontSize: '14px', alignSelf: 'center', width: '100%' }}>
                            <strong>⚠️ Error:</strong> {error}
                        </div>
                    )}
                    
                    <div ref={messagesEndRef} />
                </div>

                <div style={{ padding: '20px', borderTop: '1px solid rgba(255, 255, 255, 0.08)', display: 'flex', gap: '12px', background: 'rgba(0,0,0,0.2)' }}>
                    <textarea
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder="Escribe un mensaje aquí... (Shift + Enter para salto de línea)"
                        style={{
                            flex: 1, padding: '12px 16px',
                            background: 'rgba(0,0,0,0.3)', border: '1px solid rgba(255,255,255,0.1)',
                            borderRadius: '10px', color: '#fff', fontSize: '14px',
                            resize: 'none', height: '50px', outline: 'none', fontFamily: 'inherit'
                        }}
                    />
                    <button
                        onClick={onSend}
                        disabled={isLoading || !inputValue.trim()}
                        style={{
                            padding: '0 20px', background: '#00D4AA', color: '#0d1117',
                            border: 'none', borderRadius: '10px', fontWeight: 'bold', cursor: (isLoading || !inputValue.trim()) ? 'not-allowed' : 'pointer',
                            opacity: (isLoading || !inputValue.trim()) ? 0.5 : 1, transition: '0.2s'
                        }}
                    >
                        Enviar
                    </button>
                </div>
            </div>
        </div>,
        document.body
    );
};

const SolarAIButton: React.FC = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [isVisible, setIsVisible] = useState(false);
    
    const [messages, setMessages] = useState<Message[]>([]);
    const [inputValue, setInputValue] = useState('');
    const [capturedLogs, setCapturedLogs] = useState('');

    // Only render the button if we are on the console page
    useEffect(() => {
        const checkVisibility = () => {
            const match = window.location.pathname.match(/^\/server\/[a-zA-Z0-9]+(\/)?$/);
            setIsVisible(!!match);
        };
        checkVisibility();
        const interval = setInterval(checkVisibility, 500);
        return () => clearInterval(interval);
    }, []);

    const captureTerminalLogs = useCallback((): string => {
        const selectors = ['.xterm-rows', '.xterm-accessibility-tree', '.xterm-accessibility', '#terminal', '.terminal-wrapper'];
        for (const selector of selectors) {
            const el = document.querySelector(selector);
            if (el) {
                const text = (el as HTMLElement).innerText || el.textContent || '';
                const lines = text.split('\n').filter(l => l.trim().length > 0);
                if (lines.length > 0) {
                    return lines.slice(-50).join('\n');
                }
            }
        }
        return '';
    }, []);

    const getServerId = (): string => {
        const match = window.location.pathname.match(/\/server\/([a-zA-Z0-9]+)/);
        return match ? match[1] : '';
    };

    const handleOpen = () => {
        setIsModalOpen(true);
        if (messages.length === 0) {
            // Automatically capture logs in the background when first opening the chat
            setCapturedLogs(captureTerminalLogs());
        }
    };

    const handleSend = async () => {
        if (!inputValue.trim()) return;
        
        const serverId = getServerId();
        if (!serverId) {
            setError('No se pudo identificar el servidor actual.');
            return;
        }

        const newMsg: Message = { role: 'user', text: inputValue };
        const updatedMessages = [...messages, newMsg];
        
        setMessages(updatedMessages);
        setInputValue('');
        setIsLoading(true);
        setError(null);

        try {
            const { data } = await http.post<AnalysisResponse>(
                '/api/client/extensions/solartools/ai/analyze',
                { 
                    messages: updatedMessages,
                    logs: messages.length === 0 ? capturedLogs : '', // Only send logs context on the very first message
                    server_id: serverId 
                }
            );

            if (data.success && data.analysis) {
                setMessages([...updatedMessages, { role: 'model', text: data.analysis }]);
            } else {
                setError(data.error || 'Error desconocido.');
            }
        } catch (err: any) {
            setError(err?.response?.data?.error || err?.message || 'Error de conexión.');
        } finally {
            setIsLoading(false);
        }
    };

    if (!isVisible) return null;

    return ReactDOM.createPortal(
        <>
            <button
                onClick={handleOpen}
                style={{
                    position: 'fixed', bottom: '40px', right: '40px',
                    zIndex: 99999999, // MAX Z-INDEX OVER NEBULA
                    display: 'flex', alignItems: 'center', gap: '12px',
                    padding: '14px 24px',
                    background: 'linear-gradient(135deg, #00D4AA 0%, #00B894 100%)',
                    color: '#0d1117', border: 'none', borderRadius: '50px',
                    fontSize: '15px', fontWeight: 700,
                    cursor: 'pointer',
                    boxShadow: '0 10px 30px rgba(0, 212, 170, 0.4)',
                    transition: '0.2s',
                }}
            >
                <span style={{ fontSize: '20px' }}>☀️</span>
                <span>Solar AI</span>
            </button>

            <AnalysisModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                messages={messages}
                isLoading={isLoading}
                error={error}
                inputValue={inputValue}
                setInputValue={setInputValue}
                onSend={handleSend}
            />
        </>,
        document.body
    );
};

export default SolarAIButton;
