class system {

    package { 'libxml2-devel-2.7.6':
        ensure => installed
    }

    package { 'openssl-devel-1.0.1e':
        ensure => installed
    }

    package { 'bzip2-devel-1.0.5':
        ensure => installed
    }

    package { 'libcurl-devel-7.19.7':
        ensure => installed
    }

    package { 'libjpeg-turbo-devel-1.2.1':
        ensure => installed
    }

    package { 'libpng-devel-1.2.49':
        ensure => installed
    }

    package { 'freetype-devel-2.3.11':
        ensure => installed
    }

    package { 'openldap-devel-2.4.23':
        ensure => installed
    }

    package { 'libxslt-devel-1.1.26':
        ensure => installed
    }

    package { 'libedit-devel-2.11':
        ensure => installed
    }

    package { 'screen':
        ensure => latest
    }

    package { 'patch-2.6':
        ensure => installed
    }

    package { 'gcc-c++-4.4.7':
        ensure => installed
    }

    Package['libxml2-devel-2.7.6']       -> Package['openssl-devel-1.0.1e']
    Package['openssl-devel-1.0.1e']      -> Package['bzip2-devel-1.0.5']
    Package['bzip2-devel-1.0.5']         -> Package['libcurl-devel-7.19.7']
    Package['libcurl-devel-7.19.7']      -> Package['libjpeg-turbo-devel-1.2.1']
    Package['libjpeg-turbo-devel-1.2.1'] -> Package['libpng-devel-1.2.49']
    Package['libpng-devel-1.2.49']       -> Package['freetype-devel-2.3.11']
    Package['freetype-devel-2.3.11']     -> Package['openldap-devel-2.4.23']
    Package['openldap-devel-2.4.23']     -> Package['libxslt-devel-1.1.26']
    Package['libxslt-devel-1.1.26']      -> Package['libedit-devel-2.11']
    Package['libedit-devel-2.11']        -> Package['screen']
    Package['screen']                    -> Package['patch-2.6']
    Package['patch-2.6']                 -> Package['gcc-c++-4.4.7']

}
