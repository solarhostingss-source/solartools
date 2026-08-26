import React, { useState, useCallback, useEffect } from 'react';
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

const AnalysisModal: React.FC<{
    isOpen: boolean;
    onClose: () => void;
    analysis: string;
    isLoading: boolean;
    error: string | null;
    manualLogs: string;
    setManualLogs: (logs: string) => void;
    onManualAnalyze: () => void;
    showManualInput: boolean;
}> = ({ isOpen, onClose, analysis, isLoading, error, manualLogs, setManualLogs, onManualAnalyze, showManualInput }) => {
    if (!isOpen) return null;

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
                maxWidth: '700px', width: '100%', maxHeight: '80vh',
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

                <div style={{ padding: '24px', overflowY: 'auto', flex: 1 }}>
                    {isLoading && (
                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '16px', padding: '40px 20px' }}>
                            <div style={{ width: '48px', height: '48px', border: '3px solid rgba(0, 212, 170, 0.2)', borderTopColor: '#00D4AA', borderRadius: '50%', animation: 'solarSpin 0.8s linear infinite' }} />
                            <p style={{ color: 'rgba(255,255,255,0.7)', fontSize: '14px', margin: 0 }}>Analizando consulta con Solar AI...</p>
                            <style>{`@keyframes solarSpin { to { transform: rotate(360deg); } }`}</style>
                        </div>
                    )}

                    {error && (
                        <div style={{ background: 'rgba(255, 68, 68, 0.1)', border: '1px solid rgba(255, 68, 68, 0.2)', borderRadius: '10px', padding: '16px', color: '#FF6B6B', fontSize: '14px', marginBottom: '16px' }}>
                            <strong>⚠️ Error:</strong> {error}
                        </div>
                    )}

                    {!isLoading && showManualInput && !analysis && (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                            <p style={{ color: 'rgba(255,255,255,0.8)', margin: 0, fontSize: '14px' }}>
                                Debido al tema que estás usando, no pude capturar los logs automáticamente. Por favor, pega los logs de la consola o hazme tu pregunta aquí:
                            </p>
                            <textarea
                                value={manualLogs}
                                onChange={(e) => setManualLogs(e.target.value)}
                                placeholder="Pega aquí los logs o haz una pregunta a Solar AI..."
                                style={{
                                    width: '100%', height: '150px', padding: '12px',
                                    background: 'rgba(0,0,0,0.2)', border: '1px solid rgba(255,255,255,0.1)',
                                    borderRadius: '8px', color: '#fff', fontSize: '14px',
                                    resize: 'vertical', fontFamily: 'monospace'
                                }}
                            />
                            <button
                                onClick={onManualAnalyze}
                                disabled={!manualLogs.trim()}
                                style={{
                                    alignSelf: 'flex-end', padding: '10px 20px',
                                    background: '#00D4AA', color: '#0d1117',
                                    border: 'none', borderRadius: '8px', fontWeight: 'bold',
                                    cursor: manualLogs.trim() ? 'pointer' : 'not-allowed',
                                    opacity: manualLogs.trim() ? 1 : 0.5
                                }}
                            >
                                Analizar
                            </button>
                        </div>
                    )}

                    {!isLoading && analysis && (
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
    
    const [manualLogs, setManualLogs] = useState('');
    const [showManualInput, setShowManualInput] = useState(false);

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
        // Aggressively search for terminal text
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

    const performAnalysis = async (logs: string, serverId: string) => {
        setIsLoading(true);
        setError(null);
        setAnalysis('');
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
    };

    const handleAnalyze = useCallback(async () => {
        setIsModalOpen(true);
        setShowManualInput(false);
        setError(null);
        setAnalysis('');

        const logs = captureTerminalLogs();
        const serverId = getServerId();

        if (!serverId) {
            setError('No se pudo identificar el servidor actual.');
            return;
        }

        if (!logs) {
            setShowManualInput(true);
            return;
        }

        await performAnalysis(logs, serverId);
    }, [captureTerminalLogs]);

    const handleManualAnalyze = useCallback(() => {
        if (!manualLogs.trim()) return;
        const serverId = getServerId();
        if (!serverId) {
            setError('No se pudo identificar el servidor actual.');
            return;
        }
        setShowManualInput(false);
        performAnalysis(manualLogs, serverId);
    }, [manualLogs]);

    if (!isVisible) return null;

    return ReactDOM.createPortal(
        <>
            <button
                onClick={handleAnalyze}
                disabled={isLoading}
                style={{
                    position: 'fixed', bottom: '40px', right: '40px',
                    zIndex: 99999999, // MAX Z-INDEX OVER NEBULA
                    display: 'flex', alignItems: 'center', gap: '12px',
                    padding: '14px 24px',
                    background: 'linear-gradient(135deg, #00D4AA 0%, #00B894 100%)',
                    color: '#0d1117', border: 'none', borderRadius: '50px',
                    fontSize: '15px', fontWeight: 700,
                    cursor: isLoading ? 'wait' : 'pointer',
                    boxShadow: '0 10px 30px rgba(0, 212, 170, 0.4)',
                    opacity: isLoading ? 0.8 : 1,
                }}
            >
                <span style={{ fontSize: '20px' }}>☀️</span>
                <span>{isLoading ? 'Analizando...' : 'Solar AI'}</span>
            </button>

            <AnalysisModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                analysis={analysis}
                isLoading={isLoading}
                error={error}
                manualLogs={manualLogs}
                setManualLogs={setManualLogs}
                onManualAnalyze={handleManualAnalyze}
                showManualInput={showManualInput}
            />
        </>,
        document.body
    );
};

export default SolarAIButton;
