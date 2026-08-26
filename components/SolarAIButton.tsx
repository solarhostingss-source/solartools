import React, { useState, useCallback, useEffect, useRef } from 'react';
import ReactDOM from 'react-dom';
import http from '@/api/http';
import { ServerContext } from '@/state/server';

/**
 * ╔═══════════════════════════════════════════════════╗
 * ║  SolarTools Global Frontend Component             ║
 * ║  1. Renders SolarAI Button in a React Portal      ║
 * ║  2. Listens to WebSocket status for Webhooks      ║
 * ╚═══════════════════════════════════════════════════╝
 */

interface AnalysisResponse {
    success: boolean;
    analysis?: string;
    error?: string;
}

const AnalysisModal: React.FC<{
    isOpen: boolean;
    onClose: () => void;
    analysis: string;
    isLoading: boolean;
    error: string | null;
}> = ({ isOpen, onClose, analysis, isLoading, error }) => {
    if (!isOpen) return null;

    return ReactDOM.createPortal(
        <div style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            backgroundColor: 'rgba(0, 0, 0, 0.75)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 99999999, // Super max z-index
            padding: '20px',
        }}>
            <div style={{
                background: 'linear-gradient(135deg, #1a1d23 0%, #0d1117 100%)',
                borderRadius: '16px',
                border: '1px solid rgba(0, 212, 170, 0.2)',
                maxWidth: '700px',
                width: '100%',
                maxHeight: '80vh',
                display: 'flex',
                flexDirection: 'column',
                boxShadow: '0 25px 60px rgba(0, 0, 0, 0.5), 0 0 40px rgba(0, 212, 170, 0.1)',
            }}>
                <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    padding: '20px 24px',
                    borderBottom: '1px solid rgba(255, 255, 255, 0.08)',
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <div style={{
                            width: '36px',
                            height: '36px',
                            borderRadius: '10px',
                            background: 'linear-gradient(135deg, #00D4AA, #00B894)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: '18px',
                        }}>
                            ☀️
                        </div>
                        <div>
                            <h2 style={{ margin: 0, fontSize: '18px', fontWeight: 700, color: '#ffffff' }}>
                                Solar AI - Análisis
                            </h2>
                            <p style={{ margin: 0, fontSize: '12px', color: 'rgba(255,255,255,0.5)' }}>
                                Powered by Google Gemini
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        style={{
                            background: 'rgba(255,255,255,0.05)',
                            border: '1px solid rgba(255,255,255,0.1)',
                            borderRadius: '8px',
                            color: '#ffffff',
                            width: '32px',
                            height: '32px',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: '16px',
                        }}
                    >
                        ✕
                    </button>
                </div>

                <div style={{ padding: '24px', overflowY: 'auto', flex: 1 }}>
                    {isLoading && (
                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '16px', padding: '40px 20px' }}>
                            <div style={{ width: '48px', height: '48px', border: '3px solid rgba(0, 212, 170, 0.2)', borderTopColor: '#00D4AA', borderRadius: '50%', animation: 'solarSpin 0.8s linear infinite' }} />
                            <p style={{ color: 'rgba(255,255,255,0.7)', fontSize: '14px', margin: 0 }}>Analizando logs con Gemini AI...</p>
                            <style>{`@keyframes solarSpin { to { transform: rotate(360deg); } }`}</style>
                        </div>
                    )}

                    {error && (
                        <div style={{ background: 'rgba(255, 68, 68, 0.1)', border: '1px solid rgba(255, 68, 68, 0.2)', borderRadius: '10px', padding: '16px', color: '#FF6B6B', fontSize: '14px' }}>
                            <strong>⚠️ Error:</strong> {error}
                        </div>
                    )}

                    {!isLoading && !error && analysis && (
                        <div
                            style={{ color: 'rgba(255,255,255,0.85)', fontSize: '14px', lineHeight: '1.7', whiteSpace: 'pre-wrap' }}
                            dangerouslySetInnerHTML={{
                                __html: analysis
                                    .replace(/^### (.*$)/gm, '<h3 style="color:#00D4AA;margin:20px 0 8px;font-size:16px">$1</h3>')
                                    .replace(/^## (.*$)/gm, '<h2 style="color:#00D4AA;margin:24px 0 12px;font-size:18px">$1</h2>')
                                    .replace(/^# (.*$)/gm, '<h1 style="color:#00D4AA;margin:24px 0 12px;font-size:20px">$1</h1>')
                                    .replace(/\*\*(.*?)\*\*/g, '<strong style="color:#ffffff">$1</strong>')
                                    .replace(/`(.*?)`/g, '<code style="background:rgba(0,212,170,0.1);color:#00D4AA;padding:2px 6px;border-radius:4px;font-size:13px">$1</code>')
                                    .replace(/^- (.*$)/gm, '<li style="margin:4px 0;margin-left:16px">$1</li>')
                                    .replace(/\n/g, '<br/>'),
                            }}
                        />
                    )}
                </div>
            </div>
        </div>,
        document.body
    );
};

const SolarAIButton: React.FC = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [analysis, setAnalysis] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [isVisible, setIsVisible] = useState(false);

    // Context for Webhooks
    const status = ServerContext.useStoreState(state => state.status.value);
    const uuid = ServerContext.useStoreState(state => state.server.data?.uuid);
    const prevStatusRef = useRef<string | null>(null);

    // ── 1. Webhook Notifier Logic ────────────────────────
    useEffect(() => {
        if (status && uuid && status !== prevStatusRef.current) {
            // Only trigger if we had a previous status (prevents firing on page load)
            if (prevStatusRef.current !== null) {
                http.post(`/api/client/extensions/solartools/webhook/${uuid}/notify`, { status })
                    .catch(() => {}); // silently fail if not configured
            }
            prevStatusRef.current = status;
        }
    }, [status, uuid]);

    // ── 2. Route Checker for AI Bubble ───────────────────
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
        const terminalEl = document.querySelector('.xterm-screen');
        if (terminalEl) {
            const rows = terminalEl.querySelectorAll('.xterm-rows > div');
            const lines: string[] = [];
            rows.forEach((row) => {
                const text = (row as HTMLElement).innerText || '';
                if (text.trim()) lines.push(text);
            });
            return lines.slice(-50).join('\n');
        }
        
        const accessibilityEl = document.querySelector('.xterm-accessibility');
        if (accessibilityEl) {
            const text = accessibilityEl.textContent || '';
            const lines = text.split('\n').filter(l => l.trim());
            return lines.slice(-50).join('\n');
        }
        
        return '';
    }, []);

    const getServerId = (): string => {
        const match = window.location.pathname.match(/\/server\/([a-zA-Z0-9]+)/);
        return match ? match[1] : '';
    };

    const handleAnalyze = useCallback(async () => {
        setIsModalOpen(true);
        setIsLoading(true);
        setError(null);
        setAnalysis('');

        const logs = captureTerminalLogs();
        const serverId = getServerId();

        if (!logs) {
            setIsLoading(false);
            setError('No se pudieron capturar los logs de la consola.');
            return;
        }

        try {
            const { data } = await http.post<AnalysisResponse>(
                '/api/client/extensions/solartools/ai/analyze',
                { logs, server_id: serverId }
            );

            if (data.success && data.analysis) {
                setAnalysis(data.analysis);
            } else {
                setError(data.error || 'Error desconocido.');
            }
        } catch (err: any) {
            setError(err?.response?.data?.error || err?.message || 'Error de conexión.');
        } finally {
            setIsLoading(false);
        }
    }, [captureTerminalLogs]);

    // Render Webhook listener silently, but render Bubble via Portal ONLY if visible
    return (
        <>
            {isVisible && ReactDOM.createPortal(
                <button
                    onClick={handleAnalyze}
                    disabled={isLoading}
                    style={{
                        position: 'fixed',
                        bottom: '40px',
                        right: '40px',
                        zIndex: 99999999, // MAX Z-INDEX OVER NEBULA
                        display: 'flex',
                        alignItems: 'center',
                        gap: '12px',
                        padding: '14px 24px',
                        background: 'linear-gradient(135deg, #00D4AA 0%, #00B894 100%)',
                        color: '#0d1117',
                        border: 'none',
                        borderRadius: '50px',
                        fontSize: '15px',
                        fontWeight: 700,
                        cursor: isLoading ? 'wait' : 'pointer',
                        boxShadow: '0 10px 30px rgba(0, 212, 170, 0.4)',
                        opacity: isLoading ? 0.8 : 1,
                    }}
                >
                    <span style={{ fontSize: '20px' }}>☀️</span>
                    <span>{isLoading ? 'Analizando...' : 'Solar AI'}</span>
                </button>,
                document.body
            )}

            <AnalysisModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                analysis={analysis}
                isLoading={isLoading}
                error={error}
            />
        </>
    );
};

export default SolarAIButton;
