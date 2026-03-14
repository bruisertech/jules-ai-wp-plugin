/**
 * Jules Admin Interface
 * React App Entry Point
 */

const { useState, useEffect, createElement: el } = wp.element;
const { Button, Notice, TabPanel } = wp.components;
const apiFetch = wp.apiFetch;

if ( typeof julesGlobal !== 'undefined' && julesGlobal.nonce ) {
    apiFetch.use( apiFetch.createNonceMiddleware( julesGlobal.nonce ) );
}

const ImageSelector = () => {
    const [ products, setProducts ] = useState( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( null );
    const [ candidates, setCandidates ] = useState( [] );
    const [ activeProduct, setActiveProduct ] = useState( null );
    const [ fetchingCandidates, setFetchingCandidates ] = useState( false );
    const [ assigning, setAssigning ] = useState( false );
    const [ assignSuccess, setAssignSuccess ] = useState( null );

    const loadProducts = () => {
        setLoading( true );
        apiFetch( { path: '/jules/v1/products-no-image', method: 'GET' } )
            .then( ( response ) => {
                if( response.success && response.products ) {
                    setProducts( response.products );
                }
                setLoading( false );
            } )
            .catch( ( err ) => {
                setError( err.message );
                setLoading( false );
            } );
    };

    useEffect( () => {
        loadProducts();
    }, [] );

    const handleSearch = ( product ) => {
        setActiveProduct( product );
        setFetchingCandidates( true );
        setCandidates( [] );
        setAssignSuccess( null );
        setError( null );

        apiFetch( { path: `/jules/v1/fetch-candidates?q=${ encodeURIComponent( product.name ) }`, method: 'GET' } )
            .then( ( response ) => {
                if( response.success && response.candidates ) {
                    setCandidates( response.candidates );
                }
                setFetchingCandidates( false );
            } )
            .catch( ( err ) => {
                setError( err.message );
                setFetchingCandidates( false );
            } );
    };

    const handleAssign = ( imageUrl ) => {
        setAssigning( true );
        setAssignSuccess( null );
        setError( null );

        apiFetch( {
            path: '/jules/v1/assign-image',
            method: 'POST',
            data: { product_id: activeProduct.id, image_url: imageUrl }
        } )
        .then( ( response ) => {
            if( response.success ) {
                setAssignSuccess( 'Image assigned perfectly!' );
                // Update local list
                setProducts( products.map( p => p.id === activeProduct.id ? { ...p, current_image: response.new_image_url } : p ) );
            }
            setAssigning( false );
        } )
        .catch( ( err ) => {
            setError( err.message );
            setAssigning( false );
        } );
    };

    if ( loading ) return el( 'p', null, 'Cargando catálogo para el selector de imágenes...' );

    return el(
        'div',
        { className: 'jules-image-selector', style: { display: 'flex', gap: '20px', marginTop: '20px' } },

        // Left Column: Product List
        el(
            'div',
            { style: { width: '40%', maxHeight: '600px', overflowY: 'auto', borderRight: '1px solid #ddd', paddingRight: '20px' } },
            el( 'h3', null, 'Catálogo (Click para buscar imágenes)' ),
            products.map( p => el(
                'div',
                {
                    key: p.id,
                    onClick: () => handleSearch( p ),
                    style: {
                        display: 'flex',
                        alignItems: 'center',
                        padding: '10px',
                        borderBottom: '1px solid #eee',
                        cursor: 'pointer',
                        background: activeProduct && activeProduct.id === p.id ? '#f0f0f1' : 'transparent',
                        borderRadius: '4px'
                    }
                },
                p.current_image ? el( 'img', { src: p.current_image, style: { width: '40px', height: '40px', objectFit: 'contain', marginRight: '10px', background: '#fff', border: '1px solid #ccc' } } )
                                : el( 'div', { style: { width: '40px', height: '40px', background: '#ccc', marginRight: '10px' } } ),
                el( 'span', null, p.name )
            ) )
        ),

        // Right Column: Candidates
        el(
            'div',
            { style: { width: '60%', paddingLeft: '10px' } },
            activeProduct ? el( 'h3', null, `Buscar mejores imágenes para: ${activeProduct.name}` ) : el( 'h3', null, 'Selecciona un perfume de la lista' ),

            fetchingCandidates && el( 'p', null, '🕵️ Jules está buscando en la web fondos HD transparentes...' ),
            error && el( Notice, { status: 'error', isDismissible: true, onRemove: () => setError( null ) }, error ),
            assignSuccess && el( Notice, { status: 'success', isDismissible: true, onRemove: () => setAssignSuccess( null ) }, assignSuccess ),
            assigning && el( 'p', null, '⬇️ Descargando imagen HD y vinculando al producto en WooCommerce...' ),

            el(
                'div',
                { style: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '15px', marginTop: '15px' } },
                candidates.map( ( url, idx ) => el(
                    'div',
                    { key: idx, style: { border: '1px solid #ccc', borderRadius: '5px', padding: '10px', textAlign: 'center', background: '#fff' } },
                    el( 'img', { src: url, style: { width: '100%', height: '150px', objectFit: 'contain', marginBottom: '10px', background: 'repeating-conic-gradient(#eee 0% 25%, transparent 0% 50%) 50% / 20px 20px' } } ),
                    el(
                        Button,
                        { isPrimary: true, disabled: assigning, onClick: () => handleAssign( url ) },
                        'Usar esta imagen'
                    )
                ) )
            )
        )
    );
};

const JulesAdminApp = () => {
    const [ notice, setNotice ] = useState( null );
    const [ isUndoing, setIsUndoing ] = useState( false );
    const [ logs, setLogs ] = useState( 'Loading logs...' );

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

    useEffect( () => {
        fetchLogs();
    }, [] );

    const handleUndo = () => {
        setIsUndoing( true );
        setNotice( null );

        apiFetch( { path: '/jules/v1/undo', method: 'POST' } )
        .then( ( response ) => {
            setIsUndoing( false );
            setNotice( { status: 'success', message: response.message } );
            fetchLogs();
        } )
        .catch( ( error ) => {
            setIsUndoing( false );
            setNotice( { status: 'error', message: error.message || 'An error occurred during rollback.' } );
            fetchLogs();
        } );
    };

    const SettingsPanel = () => el(
        'div',
        null,
        el( 'p', null, 'Welcome to the autonomous control center for Bruiser Tech.' ),
        notice && el( Notice, { status: notice.status, isDismissible: true, onRemove: () => setNotice( null ) }, notice.message ),
        el(
            'div',
            { style: { marginTop: '30px' } },
            el(
                Button,
                { isPrimary: true, onClick: handleUndo, disabled: isUndoing, className: 'jules-undo-btn' },
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
                el( Button, { isSecondary: true, onClick: fetchLogs }, 'Refresh Logs' )
            ),
            el( 'div', null, logs )
        )
    );

    return el(
        'div',
        { className: 'jules-admin-dashboard', style: { maxWidth: '1200px' } },
        el( 'h1', null, '🔥 Consola de Inyección Bruiser Tech' ),

        el( TabPanel, {
            className: 'jules-tabs',
            activeClass: 'is-active',
            tabs: [
                { name: 'settings', title: 'Control General', className: 'tab-settings' },
                { name: 'images', title: 'Selector de Imágenes (HD)', className: 'tab-images' }
            ]
        },
        ( tab ) => {
            if ( tab.name === 'settings' ) {
                return el( SettingsPanel, null );
            } else if ( tab.name === 'images' ) {
                return el( ImageSelector, null );
            }
        } )
    );
};

document.addEventListener( 'DOMContentLoaded', () => {
    const rootElement = document.getElementById( 'jules-admin-root' );
    if ( rootElement ) {
        wp.element.render( el( JulesAdminApp, null ), rootElement );
    }
} );
