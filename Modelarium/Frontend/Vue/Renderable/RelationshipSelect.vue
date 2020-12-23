<template>
  <select
    :name="name"
    :class="'modelarium-relationshipselect__select ' + htmlClass"
    :required="required"
    autocomplete="off"
    :disabled="isLoading"
  >
    <option v-if="isLoading" value="" selected data-default>loading</option>
    <option v-else v-for="o in selectable" v-bind:key="o.id" :value="o.id">
      {{ o[titleField] }}
    </option>
  </select>
</template>
<script>
import axios from "axios";

export default {
  data() {
    return {
      selectable: [],
      isLoading: true,
    };
  },
  props: {
    /**
     * The form field name
     */
    name: {
      type: String,
    },
    /**
     * html classes applied on <select></select>
     */
    htmlClass: {
      type: String,
    },
    /**
     * The field in the relationship that is used as a title
     */
    titleField: {
      type: String,
    },
    /**
     * The target type, such as 'post'
     */
    targetType: {
      type: String,
    },
    /**
     * The target type plural, such as 'posts'
     */
    targetTypePlural: {
      type: String,
    },

    /**
     * The GraphQL query
     */
    query: {
      type: String,
    },

    /**
     * The GraphQL query
     */
    queryVariables: {
      type: Object,
      default: () => {
        return {};
      },
    },

    /**
     * Is this field required?
     */
    required: {
      type: Boolean,
      default: false,
    },

    /**
     * The GraphQL query
     */
    maxItems: {
      type: Number,
      default: 100,
    },
  },
  mounted() {
    this.fetch();
  },
  methods: {
    async fetch() {
      this.isLoading = true;
      return axios
        .post("/graphql", {
          query: this.query,
          variables: {
            page: 1,
            first: this.maxItems,
            ...this.queryVariables,
          },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$set(this, "selectable", data[this.targetTypePlural].data);
          // TODO: notify if more than 1 page const paginatorInfo = data[this.targetTypePlural].paginatorInfo;
          // this.$set(this, "paginatorInfo", paginatorInfo);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
