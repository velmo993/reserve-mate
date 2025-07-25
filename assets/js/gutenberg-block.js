(function(blocks, element, editor) {
    var el = element.createElement;
    var __ = wp.i18n.__;

    blocks.registerBlockType('reserve-mate/booking-form', {
        title: __('Reserve Mate Booking Form'),
        icon: 'calendar-alt',
        category: 'widgets',
        
        edit: function(props) {
            return el('div', {
                style: {
                    padding: '20px',
                    border: '2px dashed #ccc',
                    textAlign: 'center',
                    backgroundColor: '#f9f9f9'
                }
            }, [
                el('h3', {}, __('Reserve Mate Booking Form')),
                el('p', {}, __('This will display your booking form on the frontend.'))
            ]);
        },

        save: function() {
            return null;
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor
);