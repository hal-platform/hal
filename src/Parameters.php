<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI;

use InvalidArgumentException;

/**
 * This is just so we centralize these values and they are easy to find/change.
 * Longer term these are more modularized so you can dynamically add a type (and all of its parameters).
 *
 * Please note that templates still use the values, so we need to figure that out.
 */
class Parameters
{
    // targets
    public const TARGET_REGION = 'region';
    public const TARGET_CONTEXT = 'context';

    public const TARGET_EB_APP = 'application';
    public const TARGET_EB_ENV = 'environment';

    public const TARGET_CD_APP = 'application';
    public const TARGET_CD_GROUP = 'group';
    public const TARGET_CD_CONFIG = 'configuration';

    public const TARGET_S3_METHOD = 's3_method';
    public const TARGET_S3_BUCKET = 'bucket';
    public const TARGET_S3_LOCAL_PATH = 'source';
    public const TARGET_S3_REMOTE_PATH = 'path';

    public const TARGET_RSYNC_REMOTE_PATH = 'path';
    public const TARGET_RSYNC_SERVERS = 'servers';

    public const TARGET_S3_METHODS = ['sync', 'artifact']; // doesnt belong here

    // identity providers
    public const IDP_LDAP_HOST = 'ldap.host';
    public const IDP_LDAP_DOMAIN = 'ldap.domain';
    public const IDP_LDAP_BASE_DN = 'ldap.base_dn';
    public const IDP_LDAP_UNIQUE_ID = 'ldap.attr.unique_id';

    public const IDP_GH_CLIENT_ID = 'gh.client_id';
    public const IDP_GH_CLIENT_SECRET = 'gh.client_secret';

    public const IDP_GHE_CLIENT_ID = 'ghe.client_id';
    public const IDP_GHE_CLIENT_SECRET = 'ghe.client_secret';
    public const IDP_GHE_URL = 'gh.url';

    // identities
    public const ID_LDAP_ID = 'ldap.id';
    public const ID_LDAP_USERNAME = 'ldap.username';

    public const ID_INTERNAL_PASSWORD = 'internal.password';
    public const ID_INTERNAL_SETUP_TOKEN = 'internal.setup_token';
    public const ID_INTERNAL_SETUP_EXPIRY = 'internal.setup_token_expiry';

    // version control
    public const VCS_GHE_TOKEN = 'ghe.token';
    public const VCS_GHE_URL = 'ghe.url';

    public const VC_GH_OWNER = 'gh.owner';
    public const VC_GH_REPO = 'gh.repo';
}
