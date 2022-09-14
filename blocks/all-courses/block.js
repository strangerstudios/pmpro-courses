
const { __ } = wp.i18n;

const {
  registerBlockType,
} = wp.blocks;

const {
  PanelBody,
  TextControl,
} = wp.components;

const {
  InspectorControls,
} = wp.editor;

export default registerBlockType(
    'pmpro-courses/all-courses',
    {
        title: __( 'PMPro Courses: All Courses', 'pmpro-member-directory' ),
        description: __( 'Display all courses available.', 'pmpro-member-directory' ),
        category: 'pmpro',
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'admin-users',
         },        
        keywords: [
            __( 'Membership', 'jsforwpblocks' ),
            __( 'All', 'jsforwpblocks' ),
            __( 'All Courses', 'jsforwpblocks' ),
        ],
        attributes: {
          limit: {
            type: 'string',
            default: '-1'
          },
        },
        edit: props => {
            
            const { attributes:  { className, limit }, setAttributes } = props;
            
            const onChangeLimit = ( newLimit ) => {
                setAttributes( { limit: newLimit } );
            };

            return [ 
            <InspectorControls>
                <PanelBody>
                    <TextControl
                        label={ __( 'Limit', 'pmpro-courses' ) }
                        value={ limit } 
                        onChange={ limit => setAttributes( { limit: limit } ) }
                        help={ __('How many courses should be displayed. Set the value to -1 to show all courses.', 'pmpro-courses' ) }
                    />
                </PanelBody>
            </InspectorControls>,
            <div className="pmpro-block-element">
               <span className="pmpro-block-title">{ __( 'Paid Memberships Pro Courses', 'pmpro-courses' ) }</span>
               <span className="pmpro-block-subtitle">{ __( 'All Courses', 'pmpro-courses' ) }</span>
             </div>
          ];
        },
        save: props => {
          return null;
        },
    },
);
