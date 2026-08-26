import React, { useState, useRef, useEffect, useCallback } from 'react';
import ReactDOM from 'react-dom';
import http from '@/api/http';

interface FunctionCall {
    name: string;
    args: {
        command: string;
        explanation: string;
    };
}

interface Message {
    role: 'user' | 'model';
    text?: string;
    functionCall?: FunctionCall;
}

interface AnalysisResponse {
    success: boolean;
    analysis?: string;
    functionCall?: FunctionCall;
    error?: string;
}

const AnalysisModal: React.FC<{
    isOpen: boolean;
    onClose: () => void;
    messages: Message[];
    isLoading: boolean;
    error: string | null;
    inputValue: string;
    setInputValue: (val: string) => void;
    onSend: (msg?: Message) => void;
}> = ({ isOpen, onClose, messages, isLoading, error, inputValue, setInputValue, onSend }) => {
    const messagesEndRef = useRef<HTMLDivElement>(null);
    
    useEffect(() => {
        if (isOpen && messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages, isLoading, isOpen]);

    if (!isOpen) return null;

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            onSend();
        }
    };

    const getServerId = (): string => {
        const match = window.location.pathname.match(/\/server\/([a-zA-Z0-9]+)/);
        return match ? match[1] : '';
    };

    const handleApproveCommand = async (command: string) => {
        try {
            const serverId = getServerId();
            await http.post(`/api/client/servers/${serverId}/command`, { command });
            
            // Send back to AI
            const systemMsg: Message = { role: 'user', text: `[SISTEMA]: El usuario ha APROBADO la petición. El comando '${command}' fue ejecutado exitosamente en la consola.` };
            onSend(systemMsg);
        } catch (err: any) {
            const systemMsg: Message = { role: 'user', text: `[SISTEMA]: El usuario APROBÓ la petición, pero falló al ejecutar el comando. Error: ${err.message}` };
            onSend(systemMsg);
        }
    };

    const handleDenyCommand = () => {
        const systemMsg: Message = { role: 'user', text: `[SISTEMA]: El usuario ha DENEGADO la petición para ejecutar el comando. No se ha ejecutado nada.` };
        onSend(systemMsg);
    };

    return ReactDOM.createPortal(
        <div style={{
            position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
            background: 'rgba(0, 0, 0, 0.75)', backdropFilter: 'blur(10px)',
            zIndex: 9999999, display: 'flex', alignItems: 'center', justifyContent: 'center'
        }}>
            <div style={{
                background: '#0d1117', width: '90%', maxWidth: '700px', height: '80vh',
                borderRadius: '20px', display: 'flex', flexDirection: 'column',
                boxShadow: '0 20px 50px rgba(0,0,0,0.5)', overflow: 'hidden', border: '1px solid rgba(255, 255, 255, 0.1)'
            }}>
                <div style={{
                    padding: '20px 24px', borderBottom: '1px solid rgba(255, 255, 255, 0.08)',
                    display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: '#161b22'
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <span style={{ fontSize: '24px' }}>☀️</span>
                        <h2 style={{ margin: 0, fontSize: '18px', fontWeight: 600, color: '#fff' }}>Solar AI Console</h2>
                    </div>
                    <button onClick={onClose} style={{ background: 'transparent', border: 'none', color: '#8b949e', fontSize: '24px', cursor: 'pointer' }}>×</button>
                </div>

                <div style={{ flex: 1, overflowY: 'auto', padding: '24px', display: 'flex', flexDirection: 'column', gap: '16px' }}>
                    {messages.length === 0 && (
                        <div style={{ margin: 'auto', textAlign: 'center', color: '#8b949e' }}>
                            <span style={{ fontSize: '40px', display: 'block', marginBottom: '10px' }}>👋</span>
                            <p>¡Hola! Soy Solar AI, tu asistente técnico avanzado.</p>
                            <p style={{ fontSize: '13px' }}>Puedo leer tus logs y ayudarte con comandos. ¿En qué te ayudo hoy?</p>
                        </div>
                    )}
                    
                    {messages.map((msg, idx) => {
                        // Skip internal system messages in the UI
                        if (msg.text?.startsWith('[SISTEMA]:')) return null;

                        return (
                            <div key={idx} style={{
                                alignSelf: msg.role === 'user' ? 'flex-end' : 'flex-start',
                                background: msg.role === 'user' ? '#00D4AA' : '#21262d',
                                color: msg.role === 'user' ? '#000' : '#c9d1d9',
                                padding: '12px 18px', borderRadius: '14px', maxWidth: '80%',
                                borderBottomRightRadius: msg.role === 'user' ? '4px' : '14px',
                                borderBottomLeftRadius: msg.role === 'model' ? '4px' : '14px',
                                fontSize: '14px', lineHeight: '1.5',
                            }}>
                                {msg.text && (
                                    <div dangerouslySetInnerHTML={{
                                        __html: msg.text
                                            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                            .replace(/`(.*?)`/g, '<code style="background:rgba(0,0,0,0.2);padding:2px 6px;border-radius:4px">$1</code>')
                                            .replace(/^- (.*$)/gm, '<li style="margin:4px 0;margin-left:16px">$1</li>')
                                            .replace(/\n/g, '<br/>'),
                                    }} />
                                )}

                                {msg.functionCall && (
                                    <div style={{ 
                                        background: 'rgba(0, 0, 0, 0.2)', padding: '16px', 
                                        borderRadius: '8px', marginTop: msg.text ? '10px' : '0', 
                                        border: '1px solid rgba(0, 212, 170, 0.4)' 
                                    }}>
                                        <h4 style={{ margin: '0 0 10px 0', color: '#00D4AA', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                            ⚠️ Permiso Requerido
                                        </h4>
                                        <p style={{ margin: '0 0 8px 0', fontSize: '13px', color: 'rgba(255,255,255,0.9)' }}>
                                            <strong>Comando propuesto:</strong> <code style={{ background: '#000', padding: '2px 6px', borderRadius: '4px' }}>{msg.functionCall.args.command}</code>
                                        </p>
                                        <p style={{ margin: '0 0 16px 0', fontSize: '13px', color: 'rgba(255,255,255,0.7)' }}>
                                            <strong>Razón:</strong> {msg.functionCall.args.explanation}
                                        </p>
                                        
                                        {idx === messages.length - 1 ? (
                                            <div style={{ display: 'flex', gap: '10px' }}>
                                                <button onClick={() => handleApproveCommand(msg.functionCall!.args.command)} style={{ background: '#00D4AA', color: '#000', border: 'none', padding: '8px 16px', borderRadius: '6px', cursor: 'pointer', fontWeight: 'bold', flex: 1 }}>✅ Ejecutar</button>
                                                <button onClick={handleDenyCommand} style={{ background: 'rgba(255, 68, 68, 0.2)', color: '#FF6B6B', border: '1px solid rgba(255, 68, 68, 0.5)', padding: '8px 16px', borderRadius: '6px', cursor: 'pointer', fontWeight: 'bold', flex: 1 }}>❌ Denegar</button>
                                            </div>
                                        ) : (
                                            <div style={{ color: '#8b949e', fontSize: '12px', fontStyle: 'italic', textAlign: 'center' }}>Acción ya respondida</div>
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })}

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
                        onClick={() => onSend()}
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
            setCapturedLogs(captureTerminalLogs());
        }
    };

    const handleSend = async (overrideMsg?: Message) => {
        // Use overrideMsg (for system replies) or inputValue
        const textToSend = overrideMsg ? overrideMsg.text : inputValue;
        if (!textToSend?.trim()) return;
        
        const serverId = getServerId();
        if (!serverId) {
            setError('No se pudo identificar el servidor actual.');
            return;
        }

        const newMsg: Message = overrideMsg || { role: 'user', text: textToSend };
        const updatedMessages = [...messages, newMsg];
        
        setMessages(updatedMessages);
        if (!overrideMsg) setInputValue('');
        
        setIsLoading(true);
        setError(null);

        try {
            const { data } = await http.post<AnalysisResponse>(
                '/api/client/extensions/solartools/ai/analyze',
                { 
                    messages: updatedMessages,
                    logs: messages.length === 0 ? capturedLogs : '', 
                    server_id: serverId 
                }
            );

            if (data.success && (data.analysis || data.functionCall)) {
                setMessages([...updatedMessages, { 
                    role: 'model', 
                    text: data.analysis,
                    functionCall: data.functionCall 
                }]);
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
                    zIndex: 99999999,
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
