<?php

namespace WPGraphQLGravityForms\Connections;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Error\UserError;
use WPGraphQL\AppContext;
use WPGraphQLGravityForms\Interfaces\Hookable;
use WPGraphQLGravityForms\Interfaces\Connection;
use WPGraphQLGravityForms\Types\Form\Form;

class RootQueryFormsConnection implements Hookable, Connection {
    /**
     * The from field name.
     */
    const FROM_FIELD = 'gravityFormsForms';

    public function register_hooks() {
        add_action('init', [ $this, 'register_connection' ] );
    }

    public function register_connection() {
        register_graphql_connection( [
            'fromType'      => 'RootQuery',
            'toType'        => Form::TYPE,
            'fromFieldName' => self::FROM_FIELD,
            'connectionArgs' => [
                'formIds' => [
                    'type'        => [ 'list_of' => 'ID' ],
                    'description' => __( 'Array of form IDs to limit the forms to. Exclude this argument to query all forms.', 'wp-graphql-gravity-forms' ),
                ],
            ],
            'resolve' => function( $root, array $args, AppContext $context, ResolveInfo $info ) : array {
                /**
                 * Filter to control whether the user should be allowed to view forms.
                 *
                 * @param bool  Whether the current user should be allowed to view forms.
                 * @param array The form IDs to get forms by.
                 */
                $can_user_view_forms = apply_filters( 'wp_graphql_gf_can_view_forms', current_user_can( 'gravityforms_preview_forms' ), $this->get_form_ids( $args ) );

                if ( ! $can_user_view_forms ) {
                    throw new UserError( __( 'Sorry, you are not allowed to view Gravity Forms forms.', 'wp-graphql-gravity-forms' ) );
                }

                return ( new RootQueryFormsConnectionResolver( $root, $args, $context, $info ) )->get_connection();
            },
        ] );
    }

    private function get_form_ids( array $args ) : array {
        if ( isset( $args['where']['formIds'] ) && is_array( $args['where']['formIds'] ) ) {
            return array_map( 'absint', $args['where']['formIds'] );
        }

        return [];
    }
}
