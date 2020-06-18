<template>
<div>
    <a :href="link">
        {{id}}
    </a>
</div>
</template>
<script>
export default {
	data() {
		return {
		}
    },

    computed: {
        link() {
            return '/{{modelNameLower}}/' + this.id;
        }
    },

	props: {{props}}
}
</script>
<style>
</style>

