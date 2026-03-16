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
    const [ searchQuery, setSearchQuery ] = useState( '' );

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

    const executeSearch = ( queryTerm ) => {
        setFetchingCandidates( true );
        setCandidates( [] );
        setAssignSuccess( null );
        setError( null );

        apiFetch( { path: `/jules/v1/fetch-candidates?q=${ encodeURIComponent( queryTerm ) }`, method: 'GET' } )
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

    const handleSearch = ( product ) => {
        setActiveProduct( product );
        // Define default search string to show user what is being searched
        const defaultQuery = `${ product.name } parfum`;
        setSearchQuery( defaultQuery );
        executeSearch( defaultQuery );
    };

    const handleSearchInputKeyDown = ( e ) => {
        if ( e.key === 'Enter' ) {
            executeSearch( searchQuery );
        }
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
            activeProduct ? el(
                'div', null,
                el( 'h3', { style: { marginBottom: '5px' } }, `Imágenes para: ${activeProduct.name}` ),
                el( 'label', { style: { fontSize: '13px', display: 'block', marginBottom: '5px', color: '#666' } }, 'Términos de búsqueda en tiempo real (Edita y presiona Enter para re-buscar):' ),
                el( 'input', {
                    type: 'text',
                    value: searchQuery,
                    onChange: (e) => setSearchQuery(e.target.value),
                    onKeyDown: handleSearchInputKeyDown,
                    style: { width: '100%', padding: '8px', marginBottom: '15px', border: '1px solid #8c8f94', borderRadius: '4px' },
                    disabled: fetchingCandidates
                })
            ) : el( 'h3', null, 'Selecciona un perfume de la lista' ),

            fetchingCandidates && el( 'p', null, '🕵️ Jules está rastreando la web con tus términos de búsqueda actuales...' ),
            error && el( Notice, { status: 'error', isDismissible: true, onRemove: () => setError( null ) }, error ),
            assignSuccess && el( Notice, { status: 'success', isDismissible: true, onRemove: () => setAssignSuccess( null ) }, assignSuccess ),
            assigning && el( 'p', null, '⬇️ Descargando imagen y vinculando al producto en WooCommerce...' ),

            el(
                'div',
                { style: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '20px', marginTop: '15px', maxHeight: '700px', overflowY: 'auto', paddingRight: '10px' } },
                candidates.map( ( url, idx ) => el(
                    'div',
                    { key: idx, style: { border: '1px solid #ccc', borderRadius: '5px', padding: '15px', textAlign: 'center', background: '#fff', boxShadow: '0 2px 5px rgba(0,0,0,0.05)' } },
                    el( 'img', { src: url, style: { width: '100%', height: '250px', objectFit: 'contain', marginBottom: '15px', background: 'repeating-conic-gradient(#f9f9f9 0% 25%, transparent 0% 50%) 50% / 20px 20px' } } ),
                    el(
                        Button,
                        { isPrimary: true, disabled: assigning, onClick: () => handleAssign( url ), style: { width: '100%' } },
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
                { name: 'images', title: 'Selector de Imágenes (HD)', className: 'tab-images' },
                { name: 'prices', title: 'Comparador de Precios', className: 'tab-prices' }
            ]
        },
        ( tab ) => {
            if ( tab.name === 'settings' ) {
                return el( SettingsPanel, null );
            } else if ( tab.name === 'images' ) {
                return el( ImageSelector, null );
            } else if ( tab.name === 'prices' ) {
                return el( PriceTracker, null );
            }
        } )
    );
};

const PriceTracker = () => {
    const [ products, setProducts ] = useState( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( null );
    const [ activeProduct, setActiveProduct ] = useState( null );
    const [ scanning, setScanning ] = useState( false );
    const [ priceData, setPriceData ] = useState( null );

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

    const handleScan = ( product ) => {
        setActiveProduct( product );
        setScanning( true );
        setPriceData( null );
        setError( null );

        apiFetch( { path: `/jules/v1/compare-price?product_id=${ product.id }`, method: 'GET' } )
            .then( ( response ) => {
                if( response.success ) {
                    setPriceData( response );
                }
                setScanning( false );
            } )
            .catch( ( err ) => {
                setError( err.message );
                setScanning( false );
            } );
    };

    if ( loading ) return el( 'p', null, 'Cargando catálogo para el escáner de precios...' );

    return el(
        'div',
        { className: 'jules-price-tracker', style: { display: 'flex', gap: '20px', marginTop: '20px' } },

        // Left Column: Product List
        el(
            'div',
            { style: { width: '40%', maxHeight: '600px', overflowY: 'auto', borderRight: '1px solid #ddd', paddingRight: '20px' } },
            el( 'h3', null, 'Catálogo (Click para escanear internet)' ),
            products.map( p => el(
                'div',
                {
                    key: p.id,
                    onClick: () => handleScan( p ),
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

        // Right Column: Price Results
        el(
            'div',
            { style: { width: '60%', paddingLeft: '10px' } },
            activeProduct ? el( 'h3', null, `Analizando competencia de: ${activeProduct.name}` ) : el( 'h3', null, 'Selecciona un perfume para comparar' ),

            scanning && el( 'div', { style: { marginTop: '20px', fontSize: '16px' } }, '🤖 Jules está escaneando Falabella, Notino, MercadoLibre y e-commerces colombianos...' ),
            error && el( Notice, { status: 'error', isDismissible: true, onRemove: () => setError( null ) }, error ),

            priceData && (priceData.success === false ?
                el( Notice, { status: 'warning', isDismissible: false }, priceData.message )
                :
                el(
                    'div',
                    { style: { marginTop: '20px', padding: '20px', borderRadius: '8px', background: '#f9f9f9', border: '1px solid #ddd' } },
                    el( 'h4', { style: { margin: '0 0 15px 0', fontSize: '20px', color: '#333', textAlign: 'center' } }, 'Análisis Competitivo (Jules Mega Expert)' ),

                    // Main KPIs
                    el(
                        'div',
                        { style: { display: 'flex', justifyContent: 'space-between', marginBottom: '30px', padding: '15px', background: '#fff', borderRadius: '6px', boxShadow: '0 1px 3px rgba(0,0,0,0.05)' } },
                        el(
                            'div',
                            { style: { textAlign: 'center', width: '48%' } },
                            el( 'span', { style: { color: '#888', fontSize: '14px', textTransform: 'uppercase' } }, 'Tu Precio' ),
                            el( 'div', { style: { fontSize: '26px', fontWeight: 'bold', color: priceData.status === 'high' ? '#dc3232' : '#2271b1' } }, `$${new Intl.NumberFormat('es-CO').format(priceData.my_price)}` )
                        ),
                        el( 'div', { style: { width: '1px', background: '#eee' } } ),
                        el(
                            'div',
                            { style: { textAlign: 'center', width: '48%' } },
                            el( 'span', { style: { color: '#888', fontSize: '14px', textTransform: 'uppercase' } }, 'Promedio Mercado' ),
                            el( 'div', { style: { fontSize: '26px', fontWeight: 'bold', color: '#1a1a1a' } }, `$${new Intl.NumberFormat('es-CO').format(priceData.average_market_price)}` )
                        )
                    ),

                    // Status Banner
                    el(
                        'div',
                        { style: {
                            padding: '15px',
                            textAlign: 'center',
                            borderRadius: '4px',
                            marginBottom: '30px',
                            background: priceData.status === 'lowest' ? '#e1faea' : (priceData.status === 'high' ? '#fcf0f1' : '#f0f6fc'),
                            border: `1px solid ${priceData.status === 'lowest' ? '#46b450' : (priceData.status === 'high' ? '#dc3232' : '#2271b1')}`,
                            color: priceData.status === 'lowest' ? '#005a0b' : (priceData.status === 'high' ? '#8a2424' : '#043959')
                        } },
                        el( 'span', { style: { fontSize: '24px', display: 'block', marginBottom: '5px' } },
                            priceData.status === 'lowest' ? '🏆 LÍDER EN PRECIO' : (priceData.status === 'high' ? '⚠️ SOBREPRECIO DETECTADO' : '⚖️ PRECIO COMPETITIVO')
                        ),
                        el( 'span', { style: { fontWeight: 'bold' } }, priceData.message )
                    ),

                    // The 3-Column Offers Table
                    el(
                        'div',
                        { style: { display: 'flex', gap: '15px' } },

                        // Column 1: Cheaper
                        el(
                            'div',
                            { style: { flex: 1, background: '#fff', border: '1px solid #ffb900', borderRadius: '4px', padding: '10px' } },
                            el( 'h5', { style: { marginTop: 0, borderBottom: '1px solid #eee', paddingBottom: '8px', color: '#d63638', textAlign: 'center' } }, '📉 Más Baratos (Riesgo)' ),
                            priceData.offers.cheaper.length === 0 ? el( 'p', { style: { fontSize: '12px', textAlign: 'center', color: '#999' } }, 'Nadie vende más barato que tú.' ) :
                            priceData.offers.cheaper.map( (offer, i) => el(
                                'div', { key: i, style: { marginBottom: '10px', fontSize: '13px' } },
                                el( 'strong', { style: { display: 'block', color: '#d63638' } }, `$${new Intl.NumberFormat('es-CO').format(offer.price)}` ),
                                el( 'span', { style: { color: '#666', display: 'block', textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: 'nowrap' } }, offer.domain ),
                                el( 'a', { href: offer.url, target: '_blank', style: { fontSize: '11px', textDecoration: 'none' } }, '🔗 Visitar' )
                            ))
                        ),

                        // Column 2: Equal
                        el(
                            'div',
                            { style: { flex: 1, background: '#fff', border: '1px solid #2271b1', borderRadius: '4px', padding: '10px' } },
                            el( 'h5', { style: { marginTop: 0, borderBottom: '1px solid #eee', paddingBottom: '8px', color: '#2271b1', textAlign: 'center' } }, '⚖️ Mismo Precio (Empate)' ),
                            priceData.offers.equal.length === 0 ? el( 'p', { style: { fontSize: '12px', textAlign: 'center', color: '#999' } }, 'No se detectaron empates exactos.' ) :
                            priceData.offers.equal.map( (offer, i) => el(
                                'div', { key: i, style: { marginBottom: '10px', fontSize: '13px' } },
                                el( 'strong', { style: { display: 'block', color: '#2271b1' } }, `$${new Intl.NumberFormat('es-CO').format(offer.price)}` ),
                                el( 'span', { style: { color: '#666', display: 'block', textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: 'nowrap' } }, offer.domain ),
                                el( 'a', { href: offer.url, target: '_blank', style: { fontSize: '11px', textDecoration: 'none' } }, '🔗 Visitar' )
                            ))
                        ),

                        // Column 3: Expensive
                        el(
                            'div',
                            { style: { flex: 1, background: '#fff', border: '1px solid #46b450', borderRadius: '4px', padding: '10px' } },
                            el( 'h5', { style: { marginTop: 0, borderBottom: '1px solid #eee', paddingBottom: '8px', color: '#46b450', textAlign: 'center' } }, '📈 Más Caros (Margen)' ),
                            priceData.offers.expensive.length === 0 ? el( 'p', { style: { fontSize: '12px', textAlign: 'center', color: '#999' } }, 'Nadie vende más caro que tú.' ) :
                            priceData.offers.expensive.map( (offer, i) => el(
                                'div', { key: i, style: { marginBottom: '10px', fontSize: '13px' } },
                                el( 'strong', { style: { display: 'block', color: '#46b450' } }, `$${new Intl.NumberFormat('es-CO').format(offer.price)}` ),
                                el( 'span', { style: { color: '#666', display: 'block', textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: 'nowrap' } }, offer.domain ),
                                el( 'a', { href: offer.url, target: '_blank', style: { fontSize: '11px', textDecoration: 'none' } }, '🔗 Visitar' )
                            ))
                        )
                    )
                )
            )
        )
    );
};

document.addEventListener( 'DOMContentLoaded', () => {
    const rootElement = document.getElementById( 'jules-admin-root' );
    if ( rootElement ) {
        wp.element.render( el( JulesAdminApp, null ), rootElement );
    }
} );
