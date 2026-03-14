/**
 * Jules Admin Interface
 * React App Entry Point
 */

const { useState, useEffect, createElement: el } = wp.element;
const { Button, Notice } = wp.components;
const apiFetch = wp.apiFetch;

// Optional: Set up apiFetch nonce if it isn't automatically handled by WP
if ( typeof julesGlobal !== 'undefined' && julesGlobal.nonce ) {
    apiFetch.use( apiFetch.createNonceMiddleware( julesGlobal.nonce ) );
}

const JulesAdminApp = () => {
    const [ notice, setNotice ] = useState( null );
    const [ isUndoing, setIsUndoing ] = useState( false );
    const [ logs, setLogs ] = useState( 'Loading logs...' );

    // Auto-dismiss notice
    useEffect( () => {
        if ( notice ) {
            const timer = setTimeout( () => setNotice( null ), 5000 );
            return () => clearTimeout( timer );
        }
    }, [ notice ] );

    const fetchLogs = () => {
        apiFetch( { path: '/jules/v1/log', method: 'GET' } )
            .then( ( response ) => {
                setLogs( response.log || 'No logs available.' );
            } )
            .catch( ( error ) => {
                setLogs( 'Error fetching logs: ' + error.message );
            } );
    };

    // Fetch logs on mount
    useEffect( () => {
        fetchLogs();
    }, [] );

    const handleUndo = () => {
        setIsUndoing( true );
        setNotice( null );

        apiFetch( {
            path: '/jules/v1/undo',
            method: 'POST',
        } )
        .then( ( response ) => {
            setIsUndoing( false );
            setNotice( { status: 'success', message: response.message } );
            fetchLogs(); // Re-fetch logs after a successful undo
        } )
        .catch( ( error ) => {
            setIsUndoing( false );
            const errMsg = error.message || 'An error occurred during rollback.';
            setNotice( { status: 'error', message: errMsg } );
            fetchLogs(); // Re-fetch logs to capture the error if recorded
        } );
    };

    return el(
        'div',
        { className: 'jules-admin-dashboard' },
        el( 'h1', null, '🔥 Consola de Inyección Bruiser Tech' ),
        el( 'p', null, 'Welcome to the autonomous control center for Bruiser Tech.' ),

        notice && el(
            Notice,
            {
                status: notice.status,
                isDismissible: true,
                onRemove: () => setNotice( null ),
            },
            notice.message
        ),

        el(
            'div',
            { style: { marginTop: '30px' } },
            el(
                Button,
                {
                    isPrimary: true,
                    onClick: handleUndo,
                    disabled: isUndoing,
                    className: 'jules-undo-btn',
                },
                isUndoing ? 'Restoring Snapshot...' : 'DESHACER ÚLTIMO CAMBIO (CTRL+Z)'
            )
        ),

        el(
            'div',
            { className: 'jules-log-container', style: { whiteSpace: 'pre-wrap' } },
            el(
                'div',
                { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } },
                el( 'h3', { style: { marginTop: 0 } }, 'System Logs' ),
                el(
                    Button,
                    { isSecondary: true, onClick: fetchLogs },
                    'Refresh Logs'
                )
            ),
            el( 'div', null, logs )
        )
    );
};

// Mount the app
document.addEventListener( 'DOMContentLoaded', () => {
    const rootElement = document.getElementById( 'jules-admin-root' );
    if ( rootElement ) {
        wp.element.render(
            el( JulesAdminApp, null ),
            rootElement
        );
    }
} );
