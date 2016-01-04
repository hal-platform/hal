import React, { PropTypes } from 'react';

var Deployment = React.createClass({
    propTypes: {
        deployment: PropTypes.object,
        build: PropTypes.object,
        push: PropTypes.object,
        canPush: PropTypes.bool
    },

    render: function() {
        return (
            <tr>
                <td key={ this.props.deployment.id + 'a' }>
                    <span className="tooltipped tooltipped-e" aria-label={ this.props.deployment.additional }>
                        { this.props.deployment.pretty }
                    </span>
                </td>

                { this.renderPushData() }

                <td key={ this.props.deployment.id + 'f' } className="tr">{ this.renderPushable() }</td>
            </tr>
        );
    },
    renderPushData: function() {
        if (this.props.push === null) {
            return [
                <td key={ this.props.deployment.id + 'b' }></td>,
                <td key={ this.props.deployment.id + 'c' } className="table-priority-55"></td>,
                <td key={ this.props.deployment.id + 'd' }></td>,
                <td key={ this.props.deployment.id + 'e' } className="table-priority-55"></td>
            ];
        }

        return [
            <td key={ this.props.deployment.id + 'b' }>
                <a href={ this.props.build.referenceUrl }>{ this.props.build.reference }</a>
                <svg className="icon"><use xlinkHref={ '/icons.svg#' + this.props.build.referenceType }></use></svg>
            </td>,

            <td key={ this.props.deployment.id + 'c'} className="table-priority-55">
                <a href={ this.props.build.commitUrl }>{ this.props.build.commit.slice(0, 7) }</a>
                <svg className="icon"><use xlinkHref="/icons.svg#commit"></use></svg>
            </td>,

            <td key={ this.props.deployment.id + 'd' }>
                <span dangerouslySetInnerHTML={ { __html: this.renderOMGSeriouslyReactYouSuck() } } />
                (Push <a href={ this.props.push.url }>{ this.props.push.id }</a>)
            </td>,

            <td key={ this.props.deployment.id + 'e'} className="table-priority-55">
                { this.props.push.user ? this.props.push.user : 'Unknown' }
            </td>
        ];
    },
    renderPushable: function() {
        if (!this.props.canPush) {
            return 'No Access';
        }

        return (
            <label className="neatbox mrn">
                <input
                    type="checkbox"
                    className="js-pushable-deployment"
                    name="deployments[]"
                    value={ this.props.deployment.id } />
                <b className="neatbox__check"></b>Push When Ready
            </label>
        );
    },
    renderOMGSeriouslyReactYouSuck: function() {
        var time = this.props.push.inProgress ? 'Now' : this.props.push.time;

        // append an empty space because react sucks
        return time + ' ';
    }
});

var Pool = React.createClass({
    propTypes: {
        name: PropTypes.string.isRequired,
        deployments: PropTypes.array.isRequired,
        canPush: PropTypes.bool.isRequired
    },
    render: function() {
        return (
            <tbody className="table-breathe">
                { this.renderHeader() }
                { this.renderDeployments() }
            </tbody>
        );
    },
    renderHeader: function() {
        if (this.props.name !== 'err-no-view') {
            return (
                <tr>
                    <th colSpan="6" className="table-mid-header">{ this.props.name }</th>
                </tr>
            );
        }
    },
    renderDeployments: function() {
        var deployments = [];

        for (var status of this.props.deployments) {
            deployments.push(
                <Deployment
                    key={ status.deployment.id }
                    deployment={ status.deployment }
                    build={ status.build }
                    push={ status.push }
                    canPush={ this.props.canPush }
                />
            );
        }

        return deployments;
    }
});

var Table = React.createClass({
    getInitialState: function() {
        return {
            data: {
                pools: [],
                canPush: false,
                deploymentCount: 0
            }
        };
    },
    render: function() {
        return (
            <table className="table--spacing-large table--striped" data-tablesaw-mode="stack">
                <thead>
                    <tr>
                        <th>Deployment</th>
                        <th>Reference</th>
                        <th className="table-priority-55">Commit</th>
                        <th>Last Pushed</th>
                        <th className="table-priority-55">Pushed By</th>
                        <th>
                            <p className="js-toggle-container mvn tr"></p>
                            <span className="tablesaw-collapse">Deploy?</span>
                        </th>
                    </tr>
                </thead>

                { this.renderPools() }
            </table>
        );
    },
    renderPools: function() {
        if (this.state.data.deploymentCount === -1) {
            return this.renderBroken();

        } else if (this.state.data.deploymentCount === 0) {
            return this.renderEmpty();
        }

        var pools = [];
        this.state.data.pools.forEach((pool, index) => {
            if (pool.deployments.length > 0) {
                pools.push(
                    <Pool
                        key={ 'pool' + index }
                        name={ pool.name }
                        deployments={ pool.deployments }
                        canPush={ this.state.data.canPush }
                    />
                );
            }

        });

        return pools;
    },
    renderEmpty: function() {
        return (
            <tbody className="table-breathe">
                <tr>
                    <td colSpan="6">Select an environment to show available deployments.</td>
                </tr>
            </tbody>
        );
    },
    renderBroken: function() {
        return (
            <tbody className="table-breathe">
                <tr>
                    <td colSpan="6">An error occured. Cannot load deployments.</td>
                </tr>
            </tbody>
        );
    }
});

export default Table;
