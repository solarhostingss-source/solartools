import React, { useState, useCallback } from 'react';
import http from '@/api/http';

/**
 * ╔═══════════════════════════════════════════════════╗
 * ║  SolarAIButton - Console AI Analysis Component    ║
 * ║  Captures terminal output and sends it to         ║
 * ║  Gemini AI for intelligent error analysis.        ║
 * ╚═══════════════════════════════════════════════════╝
 *
 * Injected via Components.yml into ServerConsole AfterContent.
 */

// ── Types ──────────────────────────────────────────
interface AnalysisResponse {
    success: boolean;
    analysis?: string;
    error?: string;
}

// ── Modal Component ────────────────────────────────
const AnalysisModal: React.FC<{
    isOpen: boolean;
    onClose: () => void;
    analysis: string;
    isLoading: boolean;
    error: string | null;
}> = ({ isOpen, onClose, analysis, isLoading, error }) => {
    if (!isOpen) return null;

    return (
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
            zIndex: 99999,
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
                {/* ── Header ──────────────────────── */}
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
                            <h2 style={{
                                margin: 0,
                                fontSize: '18px',
                                fontWeight: 700,
                                color: '#ffffff',
                            }}>
                                Solar AI - Análisis
                            </h2>
                            <p style={{
                                margin: 0,
                                fontSize: '12px',
                                color: 'rgba(255,255,255,0.5)',
                            }}>
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
                            transition: 'all 0.2s',
                        }}
                        onMouseOver={(e) => {
                            e.currentTarget.style.background = 'rgba(255, 68, 68, 0.2)';
                            e.currentTarget.style.borderColor = 'rgba(255, 68, 68, 0.3)';
                        }}
                        onMouseOut={(e) => {
                            e.currentTarget.style.background = 'rgba(255,255,255,0.05)';
                            e.currentTarget.style.borderColor = 'rgba(255,255,255,0.1)';
                        }}
                    >
                        ✕
                    </button>
                </div>

                {/* ── Body ────────────────────────── */}
                <div style={{
                    padding: '24px',
                    overflowY: 'auto',
                    flex: 1,
                }}>
                    {isLoading && (
                        <div style={{
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            gap: '16px',
                            padding: '40px 20px',
                        }}>
                            <div style={{
                                width: '48px',
                                height: '48px',
                                border: '3px solid rgba(0, 212, 170, 0.2)',
                                borderTopColor: '#00D4AA',
                                borderRadius: '50%',
                                animation: 'solarSpin 0.8s linear infinite',
                            }} />
                            <p style={{
                                color: 'rgba(255,255,255,0.7)',
                                fontSize: '14px',
                                margin: 0,
                            }}>
                                Analizando logs con Gemini AI...
                            </p>
                            <style>{`
                                @keyframes solarSpin {
                                    to { transform: rotate(360deg); }
                                }
                            `}</style>
                        </div>
                    )}

                    {error && (
                        <div style={{
                            background: 'rgba(255, 68, 68, 0.1)',
                            border: '1px solid rgba(255, 68, 68, 0.2)',
                            borderRadius: '10px',
                            padding: '16px',
                            color: '#FF6B6B',
                            fontSize: '14px',
                        }}>
                            <strong>⚠️ Error:</strong> {error}
                        </div>
                    )}

                    {!isLoading && !error && analysis && (
                        <div
                            style={{
                                color: 'rgba(255,255,255,0.85)',
                                fontSize: '14px',
                                lineHeight: '1.7',
                                whiteSpace: 'pre-wrap',
                                fontFamily: '"Inter", -apple-system, sans-serif',
                            }}
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
        </div>
    );
};

// ── Main Button Component ──────────────────────────
const SolarAIButton: React.FC = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [analysis, setAnalysis] = useState('');
    const [error, setError] = useState<string | null>(null);

    /**
     * Capture the last 50 lines from the xterm.js terminal.
     * Tries multiple strategies to find the terminal instance.
     */
    const captureTerminalLogs = useCallback((): string => {
        // Strategy 1: Query the xterm terminal DOM directly
        const terminalEl = document.querySelector('.xterm-screen');
        if (terminalEl) {
            const rows = terminalEl.querySelectorAll('.xterm-rows > div');
            const lines: string[] = [];
            rows.forEach((row) => {
                const text = (row as HTMLElement).innerText || '';
                if (text.trim()) {
                    lines.push(text);
                }
            });
            return lines.slice(-50).join('\n');
        }

        // Strategy 2: Try to get text from xterm accessibility tree
        const accessibilityEl = document.querySelector('.xterm-accessibility');
        if (accessibilityEl) {
            const text = accessibilityEl.textContent || '';
            const lines = text.split('\n').filter(l => l.trim());
            return lines.slice(-50).join('\n');
        }

        // Strategy 3: Try to find the Terminal instance on window
        const win = window as any;
        if (win._xtermInstance) {
            const buffer = win._xtermInstance.buffer.active;
            const lines: string[] = [];
            const startRow = Math.max(0, buffer.length - 50);
            for (let i = startRow; i < buffer.length; i++) {
                const line = buffer.getLine(i);
                if (line) {
                    const text = line.translateToString(true);
                    if (text.trim()) {
                        lines.push(text);
                    }
                }
            }
            return lines.join('\n');
        }

        // Strategy 4: Fallback - grab visible text from console container
        const consoleContainer = document.querySelector('[class*="console"]') 
            || document.querySelector('#console')
            || document.querySelector('[data-testid="console"]');
        if (consoleContainer) {
            const text = (consoleContainer as HTMLElement).innerText || '';
            const lines = text.split('\n').filter(l => l.trim());
            return lines.slice(-50).join('\n');
        }

        return '';
    }, []);

    /**
     * Get the current server ID from the URL.
     */
    const getServerId = (): string => {
        const match = window.location.pathname.match(/\/server\/([a-zA-Z0-9]+)/);
        return match ? match[1] : '';
    };

    /**
     * Handle the analyze button click.
     */
    const handleAnalyze = useCallback(async () => {
        setIsModalOpen(true);
        setIsLoading(true);
        setError(null);
        setAnalysis('');

        const logs = captureTerminalLogs();
        const serverId = getServerId();

        if (!logs) {
            setIsLoading(false);
            setError('No se pudieron capturar los logs de la consola. Asegúrate de estar en la vista de consola y de que haya texto visible.');
            return;
        }

        if (!serverId) {
            setIsLoading(false);
            setError('No se pudo identificar el servidor actual.');
            return;
        }

        try {
            const { data } = await http.post<AnalysisResponse>(
                '/api/client/extensions/solartools/ai/analyze',
                {
                    logs,
                    server_id: serverId,
                }
            );

            if (data.success && data.analysis) {
                setAnalysis(data.analysis);
            } else {
                setError(data.error || 'Error desconocido al analizar los logs.');
            }
        } catch (err: any) {
            const message = err?.response?.data?.error
                || err?.message
                || 'Error de conexión con el servidor.';
            setError(message);
        } finally {
            setIsLoading(false);
        }
    }, [captureTerminalLogs]);

    return (
        <>
            {/* ── Analyze Button ──────────────────── */}
            <button
                onClick={handleAnalyze}
                disabled={isLoading}
                style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '8px',
                    padding: '10px 20px',
                    background: 'linear-gradient(135deg, #00D4AA 0%, #00B894 100%)',
                    color: '#0d1117',
                    border: 'none',
                    borderRadius: '10px',
                    fontSize: '14px',
                    fontWeight: 600,
                    cursor: isLoading ? 'wait' : 'pointer',
                    transition: 'all 0.3s ease',
                    boxShadow: '0 4px 15px rgba(0, 212, 170, 0.3)',
                    marginTop: '12px',
                    opacity: isLoading ? 0.7 : 1,
                    fontFamily: '"Inter", -apple-system, sans-serif',
                }}
                onMouseOver={(e) => {
                    if (!isLoading) {
                        e.currentTarget.style.transform = 'translateY(-2px)';
                        e.currentTarget.style.boxShadow = '0 6px 25px rgba(0, 212, 170, 0.4)';
                    }
                }}
                onMouseOut={(e) => {
                    e.currentTarget.style.transform = 'translateY(0)';
                    e.currentTarget.style.boxShadow = '0 4px 15px rgba(0, 212, 170, 0.3)';
                }}
            >
                <span style={{ fontSize: '16px' }}>☀️</span>
                {isLoading ? 'Analizando...' : 'Analizar con Solar AI'}
            </button>

            {/* ── Analysis Modal ──────────────────── */}
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
