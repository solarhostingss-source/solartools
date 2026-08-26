import React, { useState, useEffect, useCallback } from 'react';
import http from '@/api/http';

/**
 * ╔═══════════════════════════════════════════════════╗
 * ║  WebhookSettings - Discord Webhook Manager        ║
 * ║  Allows users to configure a Discord webhook      ║
 * ║  URL per server for state notifications.          ║
 * ╚═══════════════════════════════════════════════════╝
 *
 * Injected via Components.yml as a new server route /webhooks.
 */

const WebhookSettings: React.FC = () => {
    const [webhookUrl, setWebhookUrl] = useState('');
    const [savedUrl, setSavedUrl] = useState('');
    const [serverName, setServerName] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [isTesting, setIsTesting] = useState(false);
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const getServerId = (): string => {
        const match = window.location.pathname.match(/\/server\/([a-zA-Z0-9]+)/);
        return match ? match[1] : '';
    };

    const serverId = getServerId();

    // ── Load saved webhook ─────────────────────────
    useEffect(() => {
        if (!serverId) return;

        const fetchWebhook = async () => {
            try {
                const { data } = await http.get(
                    `/api/client/extensions/solartools/webhook/${serverId}`
                );
                setWebhookUrl(data.webhook || '');
                setSavedUrl(data.webhook || '');
                setServerName(data.server || '');
            } catch (err) {
                setMessage({ type: 'error', text: 'Error al cargar la configuración del webhook.' });
            } finally {
                setIsLoading(false);
            }
        };

        fetchWebhook();
    }, [serverId]);

    // ── Save webhook ───────────────────────────────
    const handleSave = useCallback(async () => {
        setIsSaving(true);
        setMessage(null);

        try {
            const { data } = await http.post(
                `/api/client/extensions/solartools/webhook/${serverId}`,
                { webhook_url: webhookUrl || null }
            );

            if (data.success) {
                setSavedUrl(webhookUrl);
                setMessage({ type: 'success', text: data.message });
            } else {
                setMessage({ type: 'error', text: data.error });
            }
        } catch (err: any) {
            setMessage({
                type: 'error',
                text: err?.response?.data?.error || 'Error al guardar el webhook.',
            });
        } finally {
            setIsSaving(false);
        }
    }, [serverId, webhookUrl]);

    // ── Test webhook ───────────────────────────────
    const handleTest = useCallback(async () => {
        setIsTesting(true);
        setMessage(null);

        try {
            const { data } = await http.post(
                `/api/client/extensions/solartools/webhook/${serverId}/test`
            );

            if (data.success) {
                setMessage({ type: 'success', text: data.message });
            } else {
                setMessage({ type: 'error', text: data.error });
            }
        } catch (err: any) {
            setMessage({
                type: 'error',
                text: err?.response?.data?.error || 'Error al enviar la prueba.',
            });
        } finally {
            setIsTesting(false);
        }
    }, [serverId]);

    // ── Styles ─────────────────────────────────────
    const containerStyle: React.CSSProperties = {
        maxWidth: '640px',
        margin: '0 auto',
        padding: '32px 24px',
        fontFamily: '"Inter", -apple-system, sans-serif',
    };

    const cardStyle: React.CSSProperties = {
        background: 'linear-gradient(135deg, #1a1d23 0%, #0d1117 100%)',
        borderRadius: '16px',
        border: '1px solid rgba(255, 255, 255, 0.08)',
        overflow: 'hidden',
    };

    const headerStyle: React.CSSProperties = {
        padding: '24px',
        borderBottom: '1px solid rgba(255, 255, 255, 0.06)',
        display: 'flex',
        alignItems: 'center',
        gap: '14px',
    };

    const iconBoxStyle: React.CSSProperties = {
        width: '44px',
        height: '44px',
        borderRadius: '12px',
        background: 'linear-gradient(135deg, #5865F2, #7289DA)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontSize: '22px',
        flexShrink: 0,
    };

    const bodyStyle: React.CSSProperties = {
        padding: '24px',
    };

    const labelStyle: React.CSSProperties = {
        display: 'block',
        fontSize: '13px',
        fontWeight: 600,
        color: 'rgba(255, 255, 255, 0.7)',
        marginBottom: '8px',
        textTransform: 'uppercase' as const,
        letterSpacing: '0.5px',
    };

    const inputStyle: React.CSSProperties = {
        width: '100%',
        padding: '12px 16px',
        background: 'rgba(255, 255, 255, 0.04)',
        border: '1px solid rgba(255, 255, 255, 0.1)',
        borderRadius: '10px',
        color: '#ffffff',
        fontSize: '14px',
        outline: 'none',
        transition: 'all 0.2s',
        boxSizing: 'border-box' as const,
        fontFamily: '"JetBrains Mono", "Fira Code", monospace',
    };

    const btnPrimaryStyle: React.CSSProperties = {
        display: 'inline-flex',
        alignItems: 'center',
        gap: '8px',
        padding: '10px 24px',
        background: 'linear-gradient(135deg, #00D4AA, #00B894)',
        color: '#0d1117',
        border: 'none',
        borderRadius: '10px',
        fontSize: '14px',
        fontWeight: 600,
        cursor: isSaving ? 'wait' : 'pointer',
        opacity: isSaving ? 0.7 : 1,
        transition: 'all 0.2s',
    };

    const btnSecondaryStyle: React.CSSProperties = {
        display: 'inline-flex',
        alignItems: 'center',
        gap: '8px',
        padding: '10px 24px',
        background: 'rgba(88, 101, 242, 0.15)',
        color: '#7289DA',
        border: '1px solid rgba(88, 101, 242, 0.3)',
        borderRadius: '10px',
        fontSize: '14px',
        fontWeight: 600,
        cursor: isTesting ? 'wait' : 'pointer',
        opacity: isTesting || !savedUrl ? 0.5 : 1,
        transition: 'all 0.2s',
    };

    if (isLoading) {
        return (
            <div style={{ ...containerStyle, textAlign: 'center', paddingTop: '80px' }}>
                <div style={{
                    width: '40px',
                    height: '40px',
                    border: '3px solid rgba(0, 212, 170, 0.2)',
                    borderTopColor: '#00D4AA',
                    borderRadius: '50%',
                    animation: 'solarSpin 0.8s linear infinite',
                    margin: '0 auto 16px',
                }} />
                <p style={{ color: 'rgba(255,255,255,0.5)', fontSize: '14px' }}>
                    Cargando configuración...
                </p>
                <style>{`@keyframes solarSpin { to { transform: rotate(360deg); } }`}</style>
            </div>
        );
    }

    return (
        <div style={containerStyle}>
            <div style={cardStyle}>
                {/* ── Header ──────────────────── */}
                <div style={headerStyle}>
                    <div style={iconBoxStyle}>🔔</div>
                    <div>
                        <h2 style={{ margin: 0, fontSize: '18px', fontWeight: 700, color: '#fff' }}>
                            Webhook de Discord
                        </h2>
                        <p style={{ margin: '4px 0 0', fontSize: '13px', color: 'rgba(255,255,255,0.45)' }}>
                            Recibe notificaciones de estado del servidor en tu canal de Discord
                        </p>
                    </div>
                </div>

                {/* ── Body ────────────────────── */}
                <div style={bodyStyle}>
                    {/* Info box */}
                    <div style={{
                        background: 'rgba(88, 101, 242, 0.08)',
                        border: '1px solid rgba(88, 101, 242, 0.15)',
                        borderRadius: '10px',
                        padding: '14px 16px',
                        marginBottom: '20px',
                        fontSize: '13px',
                        color: 'rgba(255, 255, 255, 0.65)',
                        lineHeight: 1.6,
                    }}>
                        <strong style={{ color: '#7289DA' }}>💡 ¿Cómo obtener un webhook?</strong>
                        <br />
                        Discord → Editar canal → Integraciones → Webhooks → Nuevo Webhook → Copiar URL
                    </div>

                    {/* Input */}
                    <label style={labelStyle}>URL del Webhook</label>
                    <input
                        type="url"
                        value={webhookUrl}
                        onChange={(e) => setWebhookUrl(e.target.value)}
                        placeholder="https://discord.com/api/webhooks/..."
                        style={inputStyle}
                        onFocus={(e) => {
                            e.currentTarget.style.borderColor = 'rgba(0, 212, 170, 0.4)';
                            e.currentTarget.style.boxShadow = '0 0 0 3px rgba(0, 212, 170, 0.1)';
                        }}
                        onBlur={(e) => {
                            e.currentTarget.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                            e.currentTarget.style.boxShadow = 'none';
                        }}
                    />

                    {/* Status indicator */}
                    {savedUrl && (
                        <div style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: '6px',
                            marginTop: '10px',
                            fontSize: '12px',
                            color: '#00D4AA',
                        }}>
                            <span style={{
                                width: '6px',
                                height: '6px',
                                borderRadius: '50%',
                                background: '#00D4AA',
                                display: 'inline-block',
                            }} />
                            Webhook configurado y activo
                        </div>
                    )}

                    {/* Message toast */}
                    {message && (
                        <div style={{
                            marginTop: '16px',
                            padding: '12px 16px',
                            borderRadius: '10px',
                            fontSize: '13px',
                            background: message.type === 'success'
                                ? 'rgba(0, 212, 170, 0.1)'
                                : 'rgba(255, 68, 68, 0.1)',
                            border: `1px solid ${message.type === 'success'
                                ? 'rgba(0, 212, 170, 0.2)'
                                : 'rgba(255, 68, 68, 0.2)'}`,
                            color: message.type === 'success' ? '#00D4AA' : '#FF6B6B',
                        }}>
                            {message.type === 'success' ? '✅' : '⚠️'} {message.text}
                        </div>
                    )}

                    {/* Action buttons */}
                    <div style={{
                        display: 'flex',
                        gap: '12px',
                        marginTop: '24px',
                        flexWrap: 'wrap',
                    }}>
                        <button
                            onClick={handleSave}
                            disabled={isSaving}
                            style={btnPrimaryStyle}
                        >
                            {isSaving ? '⏳ Guardando...' : '💾 Guardar Webhook'}
                        </button>
                        <button
                            onClick={handleTest}
                            disabled={isTesting || !savedUrl}
                            style={btnSecondaryStyle}
                        >
                            {isTesting ? '⏳ Enviando...' : '🧪 Enviar Prueba'}
                        </button>
                    </div>
                </div>
            </div>

            {/* ── Events info card ────────────── */}
            <div style={{
                ...cardStyle,
                marginTop: '20px',
            }}>
                <div style={{ padding: '20px 24px' }}>
                    <h3 style={{
                        margin: '0 0 12px',
                        fontSize: '15px',
                        fontWeight: 600,
                        color: '#ffffff',
                    }}>
                        📋 Eventos notificados
                    </h3>
                    <div style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
                        gap: '8px',
                    }}>
                        {[
                            { emoji: '✅', label: 'Servidor en línea', color: '#00D4AA' },
                            { emoji: '🔴', label: 'Servidor fuera de línea', color: '#FF4444' },
                            { emoji: '🔄', label: 'Servidor iniciando', color: '#FFA500' },
                            { emoji: '⏳', label: 'Servidor deteniéndose', color: '#FFD700' },
                            { emoji: '📦', label: 'Instalación completada', color: '#7289DA' },
                            { emoji: '💀', label: 'Detenido forzosamente', color: '#FF0000' },
                        ].map((event) => (
                            <div
                                key={event.label}
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '8px',
                                    padding: '8px 12px',
                                    borderRadius: '8px',
                                    background: 'rgba(255,255,255,0.03)',
                                    fontSize: '13px',
                                    color: 'rgba(255,255,255,0.65)',
                                }}
                            >
                                <span>{event.emoji}</span>
                                {event.label}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default WebhookSettings;
