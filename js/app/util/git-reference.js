function determineGitref(gitref, size = 200) {
    var formatted = formatGitref(gitref);

    if (formatted.length <= size + 3) {
        return formatted;
    } else {
        return formatted.slice(0, size) + '...';
    }
}

function determineGitrefType(gitref) {
    if (gitref.indexOf('Release') === 0) {
        return 'tag';
    }

    if (gitref.indexOf('Pull Request') === 0) {
        return 'pull';
    }

    if (gitref.indexOf('Commit') === 0) {
        return 'commit';
    }

    return 'branch';
}

function formatGitref(gitref) {
    var prRegex = /^pull\/([\d]+)$/i,
        tagRegex = /^tag\/([\x21-\x7E]+)$/i,
        commitRegex = /^[a-f0-9]{40}$/i,
        match = null;

    match = prRegex.exec(gitref);
    if (match !== null && match.length > 0) {
        return 'Pull Request ' + match.pop();
    }

    match = tagRegex.exec(gitref);
    if (match !== null && match.length > 0) {
        return 'Release ' + match.pop();
    }

    match = commitRegex.exec(gitref);
    if (match !== null && match.length == 1) {
        return 'Commit ' + match.pop().slice(0, 7);
    }

    // Must be a branch
    return gitref;
}

var component = {
    format: determineGitref,
    determineType: determineGitrefType
};

export default component;
