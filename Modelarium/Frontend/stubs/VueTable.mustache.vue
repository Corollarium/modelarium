<template>
  <main class="modelarium-table {|lowerName|}-table">
    <h1 class="modelarium-table__title {|lowerName|}-table__title">
      {|StudlyName|}
    </h1>

    <div class="modelarium-table__header {|lowerName|}-table__header">
      {|{buttonCreate}|}
    </div>

    {|{ spinner }|}

    <div
      class="modelarium-table__container {|lowerName|}-table__container"
      v-if="list.length"
    >
      {|{ tablelist }|}
      <Pagination
        v-bind="pagination"
        @page="pagination.currentPage = $event"
      ></Pagination>
    </div>
    <div class="modelarium-table__empty {|lowerName|}-table__empty" v-else>
      {{ messageNothingFound }}
    </div>
  </main>
</template>

<script>
import {|StudlyName|}TableItem from "./{|StudlyName|}TableItem";
import {|options.axios.method|} from "{|options.axios.importFile|}";
import queryTable from 'raw-loader!./queryTable.graphql';

export default {

  props: {
    filters: {
      type: Object,
      default: () => ({
        {|#filters|}
          {|name|}: undefined,
        {|/filters|}
      }),
      {|#if options.runtimeValidator|}
      validator: tObject({
        {|#each filters|}
        {|name|}: {|#if required|}tString(){|/if|}{|#unless required|}optional(tString()){|/unless|},
        {|/each|}
      }).asSuccess
      {|/if|}
    },
    queryList: {
      type: String,
      default: queryList,
    },
    // the query name (which is used to access the resulting data)
    queryName: {
      type: String,
      default: '{|lowerNamePlural|}'
    },
    // the variables for the graphql query
    queryVariables: {
      type: Object,
      default: () => ({}),
    },
    messageNothingFound: {
      type: String,
      default: '{|options.messages.nothingFound|}'
    }
  },

  data() {
    return {
      type: "{|lowerName|}",
      list: [],
      isLoading: true,
      pagination: {
        currentPage: 1,
        lastPage: 1,
        perPage: 20,
        total: 1,
        html: "",
      },
      {|{extraData}|}
    };
  },

  components: { {|StudlyName|}TableItem: {|StudlyName|}TableItem },

  created() {
    if (this.$route) {
      if (this.$route.query.page && this.$route.query.page > 1) {
        this.pagination.currentPage = this.$route.query.page;
      }
    }
    this.index(this.pagination.currentPage);
  },

  watch: {
    "pagination.currentPage": {
      handler (newVal, oldVal) {
        if (this.$route) {
          if (this.$route.query.page != newVal) {
            // TODO this.$router.push(this._indexURL(newVal));
            this.index(newVal);
          }
        }
      }
    },
  },

	methods: {
    can(ability) {
      return false;
    },

    sort(field, order) {
      // TODO
    },

    index(page) {
      this.isLoading = true;

      return {|options.axios.method|}.post(
        '/graphql',
        {
            query: this.queryTable,
            variables: { page, ...this.filters, ...this.variables },
        }
      ).then((result) => {
        if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
        }
        const resultData = result.data.data;
        if ("data" in resultData[this.queryName]) {
            this.$set(this, "list", resultData[this.queryName].data);
        } else {
            this.$set(this, "list", resultData[this.queryName]);
        }
        if ("paginatorInfo" in resultData[this.queryName]) {
            this.$set(this, "pagination", resultData[this.queryName].paginatorInfo);
        }
      }).finally(() => {
        this.isLoading = false;
      });
    }
	}
};
</script>
<style></style>
