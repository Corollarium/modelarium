<template>
  <main class="modelarium-list {|lowerName|}-list">
    <h1 class="modelarium-list__title {|lowerName|}-list__title" v-if="showTitle">{|typeTitle|}</h1>

    <div class="modelarium-list__header {|lowerName|}-list__header" v-if="showHeader && can.create">
      {|{buttonCreate}|}
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

      <Pagination
        v-if="showPagination"
        v-bind="pagination"
        @page="pagination.currentPage = $event"
      ></Pagination>
    </div>
    <div class="modelarium-list__empty {|lowerName|}-list__empty" v-else>
      Nothing found.
    </div>
  </main>
</template>

<script>
import {|StudlyName|}Card from "./{|StudlyName|}Card";
import axios from 'axios';
import listQuery from 'raw-loader!./queryList.graphql';
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
    showHeader: {
      type: Boolean,
      default: true,
    },
    showPagination: {
      type: Boolean,
      default: true,
    },
    showTitle: {
      type: Boolean,
      default: true,
    }
  },

  data() {
    return {
      type: "{|lowerName|}",
      list: [],
      isLoading: true,
      can: {
        create: true,
      },
      pagination: {
        currentPage: 1,
        lastPage: 1,
        perPage: 20,
        lastPage: 1,
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
    filters() {
      this.index(0);
    },
  },

	methods: {
    index(page) {
      this.isLoading = true;
      return axios.post(
        '/graphql',
        {
            query: listQuery,
            variables: { page, ...this.filters },
        }
      ).then((result) => {
        if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
        }
        const data = result.data.data;
        this.$set(this, 'list', data.{|lowerNamePlural|}.data);
        this.$set(this, 'pagination', data.{|lowerNamePlural|}.paginatorInfo);
      }).finally(() => {
        this.isLoading = false;
      });
    }
	}
};
</script>
<style></style>
