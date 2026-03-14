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

    // Auto-dismiss notice
    useEffect( () => {
        if ( notice ) {
            const timer = setTimeout( () => setNotice( null ), 5000 );
            return () => clearTimeout( timer );
        }
    }, [ notice ] );

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
        } )
        .catch( ( error ) => {
            setIsUndoing( false );
            const errMsg = error.message || 'An error occurred during rollback.';
            setNotice( { status: 'error', message: errMsg } );
        } );
    };

    return el(
        'div',
        { className: 'jules-admin-dashboard' },
        el( 'h1', null, 'Jules Live Dashboard' ),
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
            { className: 'jules-log-container' },
            el( 'h3', null, 'System Logs' ),
            el(
                'p',
                null,
                'Activity log can be viewed in ',
                el( 'code', null, '/jules-genesis/activity.log' ),
                '. Future updates will stream logs here directly.'
            )
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
