<template>
  <main class="modelarium-table">
    <h1 class="modelarium-table__title">{|StudlyName|}</h1>

    <div class="modelarium-table__header">
      {|{buttonCreate}|}
    </div>

    <div class="modelarium-table__container" v-if="list.length">
      {|{ tablelist }|}

      <Pagination v-bind="pagination"></Pagination>
    </div>
    <div class="modelarium-table__empty" v-else>
      Nothing found.
    </div>
  </main>
</template>

<script>
import {|StudlyName|}TableItem from "./{|StudlyName|}TableItem";
import axios from 'axios';
import tableQuery from 'raw-loader!./queryTable.graphql';

export default {
  data() {
    return {
      type: "{|lowerName|}",
      list: [],
      pagination: {
        currentPage: 1,
        lastPage: 1,
        perPage: 20,
        lastPage: 1,
        html: "",
      },
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
    sort(field, order) {
      // TODO
    },

    index(page) {
      axios.post(
        '/graphql',
        {
            query: tableQuery,
            variables: { page },
        }
      ).then((result) => {
        if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
        }
        const data = result.data.data;
        this.$set(this, 'list', data.posts.data);
        this.$set(this, 'pagination', data.posts.paginatorInfo);
      });
    }
	}
};
</script>
<style></style>
