<template>
  <main 
    v-show="!(hideNoResults && list.length == 0)"
    :class="{ 'modelarium-list': true, '{|lowerName|}-list': true, 'modelarium-list--loading': isLoading, '{|lowerName|}-list--loading': isLoading }"
    itemtype="ItemList"
  >
    <slot name="title"><h1 class="modelarium-list__title {|lowerName|}-list__title">{|typeTitle|}</h1></slot>

    <div class="modelarium-list__header {|lowerName|}-list__header">
      <slot name="header">{|{buttonCreate}|}</slot>
    </div>

    {|{ spinner }|}

    <div class="modelarium-list__list {|lowerName|}-list__list" v-if="list.length">
      <div class="modelarium-list__filters {|lowerName|}-list__filters">
        {|#filters|}

        {|/filters|}
      </div>

      <div class="modelarium-list__items {|lowerName|}-list__items">
        <{|StudlyName|}Card v-for="l in list" :key="l.id" v-bind="l"></{|StudlyName|}Card>
      </div>

      <slot name="pagination" :pagination="pagination">
        <Pagination
          v-bind="pagination"
          @page="pagination.currentPage = $event"
        ></Pagination>
      </slot>
    </div>
    <div class="modelarium-list__empty {|lowerName|}-list__empty" v-else>
      {{messageNothingFound}}
    </div>

    <div class="modelarium-list__footer {|lowerName|}-list__footer">
      <slot name="footer"></slot>
    </div>

  </main>
</template>

<script>
import {|StudlyName|}Card from "./{|StudlyName|}Card";
import {|options.vue.axios.method|} from "{|options.vue.axios.importFile|}";
import queryList from 'raw-loader!./queryList.graphql';
{|#if options.runtimeValidator|}
import { tObject, tString, tNumber, tBoolean, optional } from 'runtime-validator';
{|/if|}

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
    // don't show entire component if we get no results
    hideNoResults: {
      type: Boolean,
      default: false,
    },
    messageNothingFound: {
      type: String,
      default: '{|options.frontend.messages.nothingFound|}'
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

  components: { {|StudlyName|}Card: {|StudlyName|}Card },

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
    filters: {
      handler() {
        this.index(0);
      },
      deep: true
    },
  },

	methods: {
    can(ability) {
      return false;
    },

    index(page) {
      this.isLoading = true;
      return {|options.vue.axios.method|}.post(
        '/graphql',
        {
            query: this.queryList,
            variables: { page, ...this.filters, ...this.queryVariables },
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
