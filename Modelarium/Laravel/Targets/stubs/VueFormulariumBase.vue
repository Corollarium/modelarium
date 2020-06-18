<template>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            type: '', // the object model name
            axios: null, // axios object
            can: { // permissions
                create: false,
                delete: false,
                update: false
            },
            fields: {
                // model fields
            },
        }
    },

    created() {
        if (this.$route) {
            if (this.$route.query.page && this.$route.query.page > 1) {
                this.pagination.current_page = this.$route.query.page;
            }
        }

        this.axios = axios.create();
        this.axiosCatchErrors = axios.create();

        this.axios.interceptors.request.use(req => {
            this.$parent.isLoading = true;
            return req;
        });

        this.axios.interceptors.response.use(
            response => {
                this.$parent.isLoading = false;

                if (typeof response.data === 'string') {
                    return Promise.reject(response.data);
                }
                return typeof response.data === 'string' ? Promise.reject(response.data) : response;
            },
            err => {
                return Promise.reject(err);
            }
        );

        this.axios.interceptors.response.use(
            response => {
                // TODO this.$store.commit('updateErrors');
                return response;
            }, err => {
                this.$parent.isLoading = false;

                this.handleAjaxErrors(err);

                return Promise.reject(err);
            }
        );

        this.axiosCatchErrors.interceptors.response.use(
            response => {
                // this.$store.commit('updateErrors');

                if (typeof response.data === 'string') {
                    this.handleAjaxErrors(response.data);
                    return Promise.reject(response.data);
                }

                return response;
            }, err => {
                this.handleAjaxErrors(err);

                return Promise.reject(err);
            }
        );
    },

    methods: {
        handleAjaxErrors(err) {
            let errors = [];
            if (err.response) {
                if (err.response.status === 403) { // TODO && !this.$store.getters.isLogged()) {
                    this.redirect(this.$route.path, 'Login')
                }

                if (typeof err.response === 'string') {
                    errors = [err.response];
                } else if (typeof err.response.data === 'string') {
                    errors = [err.response.data];
                } else {
                    errors = [err.response.data.message].concat(
                        err.response.data.errors ? Object.values(err.response.data.errors) : []
                    );
                }
            } else {
                errors = [err];
            }

            // TODO this.$store.commit('updateErrors', errors);
        },

        urlPush(url, name ='') {
            if (this.$router) {
                this.$router.push({
                    name,
                    params: {
                        redirect: url
                    }
                });
            }
            else {
                window.location.href = url;
            }

            return true;
        },

        urlReplace(url, name ='') {
            if (this.$router) {
                this.$router.replace(url);
            }
            else {
                window.location.href = url;
            }

            return true;
        },

        _getData() {
            let fd = new FormData();
            for (let i in this.fields) {
                if (this.fields[i] instanceof File) { // upload
                    fd.append(i, this.fields[i]);
                }
                else if (Array.isArray(this.fields[i])) {
                    for (let j of this.fields[i]) {
                        fd.append(i + '[]', j);
                    }
                }
                else if (typeof this.fields[i] === 'object') {
                    let data = this.fields[i];
                    if (!Array.isArray(this.fields[i])) {
                        data = [this.fields[i]];
                    }
                    data.forEach(d => {
                        // handle fk
                        if (d && d.id) {
                            let assocAttr = i.replace(/([A-Z])/g, '_$1').toLowerCase() + '_id';
                            fd.append(assocAttr, d.id);
                        }
                    });
                }
                else {
                    fd.append(i, this.fields[i]);
                }
            }
            return fd;
        },

        getData() {
            return this._getData();
        },

        _indexURL(page) {
            let urlParams = [`page=${page}`];
            if (this.$route) {
                for (let key in this.$route) {
                    if (key === 'page') {
                        continue;
                    }
                    urlParams.push(`${key}=${query[key]}`);
                }
            }
            else {
                const searchParams = new URLSearchParams(location.search)
                for (const [key, value] of searchParams) {
                    if (key === 'page') {
                        continue;
                    }
                    urlParams.push(`${key}=${value}`);
                }
            }
            return `/${this.type}?${urlParams.join('&')}`;
        },

        index(page = this.pagination.current_page) {
            return this.axios.get('/api' + this._indexURL(page)).then((res) => {
                console.log(res);
                this.pagination.current_page = res.data.current_page;
                this.pagination.total = res.data.total;
                this.pagination.per_page = res.data.per_page;
                this.pagination.html = res.data.htmlPaginator;
                this.list = res.data.data;
                return res.data;
            });
        },

        postDestroy() {
            this.urlReplace(`/${this.type}`);
        },

        destroy(id) {
            if (!window.confirm('Are you sure?')) {
                return Promise.resolve();
            }
            return this.axios.delete(`/api/${this.type}/${id}`).then((res) => {
                /**
                 *  Delete potencialmente afeta suas permissões no sistema,
                 * ex: deletar seu próprio project muda seu PROJECT.CAN.STORE de false para true.
                 */
                // TODO this.$store.commit('updateCan', res.data.can);
                this.postDestroy();
            });
        },

        get(id) {
            return this.axios.get(`/api/${this.type}/${id}`).then((res) => {
                let isEdit = this.$route.path.endsWith('/edit');
                if (isEdit && res.data.can.update === false) {
                    this.urlReplace('/403');
                }

                return res.data
            }).catch(err => {
                if (err.response && err.response.status === 404) {
                    this.urlReplace(`/404`);
                }
                return Promise.reject(err);
            });
        },

        _save() {
            return (this.fields.id ? this.update(this.getData()) : this.create(this.getData())).then(res => {
                // TODO this.$store.commit('updateCan', res.data.can);
                return res.data.model;
            });
        },

        create(data) {
            return this.axios.post(
                `/api/${this.type}`,
                data,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            );
        },

        update(data) {
            data.append('_method', 'PUT');

            return this.axios.post(
                `/api/${this.type}/${this.fields.id}`,
                data,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            );
        }
    }
}
</script>
<style>
</style>

