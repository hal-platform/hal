function getUserFromEntity(entity) {
    let user = 'Unknown';

    if (entity._links && entity._links.user) {
        user = entity._links.user.title;
    }

    return user;
}

function getEnvironmentFromEntity(entity) {
    let environment = 'Any';

    if (entity._links && entity._links.environment) {
        environment = entity._links.environment.title;
    }

    if (entity._embedded && entity._embedded.environment) {
        environment = entity._embedded.environment.name;
    }

    return environment;
}

function getTargetFromEntity(entity) {
    var targetName = 'Unknown';

    if (entity._embedded && entity._embedded.target) {
        let target = entity._embedded.target,
            groupName = target._links.group.title;

        if (groupName.length === 0) {
            if (target['eb-environment'].length !== null) {
                targetName = 'EB';

            } else if (target['cd-name'].length !== null) {
                targetName = 'CD';

            } else if (target['s3-file'].length !== null) {
                targetName = 'S3';
            }
        }
    }

    return targetName;
}

function formatBuildID(id) {
    var regex = /^b[a-zA-Z0-9]{1}.[a-zA-Z0-9]{7}$/i,
        match = null;

    match = regex.exec(id);
    if (match !== null && match.length == 1) {
        return match.pop().slice(6);
    }

    return id.slice(0, 10);
}

function formatReleaseID(id) {
    var regex = /^r[a-zA-Z0-9]{1}.[a-zA-Z0-9]{7}$/i,
        match = null;

    match = regex.exec(id);
    if (match !== null && match.length == 1) {
        return match.pop().slice(6);
    }

    return id.slice(0, 10);
}

export {
    getUserFromEntity,
    getEnvironmentFromEntity,
    getTargetFromEntity,
    formatBuildID,
    formatReleaseID
};
