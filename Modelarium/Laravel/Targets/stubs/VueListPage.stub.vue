<template>
<main>
    <h1 class="title">{{modelName}}</h1>

    <div class="column has-text-right">
        <button class="button is-primary is-medium" @click="add">
            <i class="fa fa-plus"></i>
            Add your {{modelNameLower}}
        </button>
    </div>

    <div class="formularium-model-list" v-if="list.length">
        <{{modelName}}Item
            v-for="model in list"
            :key="model.id"
            v-bind:id="model.id"
            {{propsBind}}
        >
            <template slot="middle">
            </template>
        </{{modelName}}Item>

        <hr>

        <div v-html="pagination.html"></div>
    </div>
    <div v-else>
        Nothing found.
    </div>
</main>
</template>

<script>
import {{modelName}}Item from './{{modelName}}Item';
import {{modelName}}Base from './{{modelName}}Base';

export default {
	extends: {{modelName}}Base,

	data() {
		return {
			type: '{{modelNameLower}}',
            list: [],
            pagination: {
                current_page: 1,
                total: 0,
                per_page: 20,
                html: ''
            },
		}
	},

	components: { {{modelName}}Item },

	created() {
        if (this.$route) {
            if (this.$route.query.page && this.$route.query.page > 1) {
                this.pagination.current_page = this.$route.query.page;
            }
        }

        this.index();
	},

    watch: {
        'pagination.current_page': function(newVal, oldVal) {
            if (this.$route) {
                if (this.$route.query.page != newVal) {
                    this.$router.push(this._indexURL(newVal));
                    this.index(newVal);
                }
            }
        },
    },

	methods: {
        add() {
            this.urlPush(`/${this.type}/edit`);
		}
	}
};
</script>
<style>
</style>
