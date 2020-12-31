<template>
  <div class="modelarium-autocomplete" :data-attribute="name">
    <select :name="name" :multiple="isMultiple" style="display: none">
      <option
        v-for="item in selectionVisible"
        :key="item.id"
        :value="item.id"
        selected="selected"
      ></option>
    </select>
    <div class="modelarium-autocomplete__container">
      <autocomplete
        :debounceTime="debounceTime"
        :search="autocompleteSearch"
        :get-result-value="autocompleteGetResultValue"
        :placeholder="placeholder"
        :default-value="initialValue"
        :aria-label="placeholder"
        @submit="onSubmit"
      >
        <template
          #default="{
            rootProps,
            inputProps,
            inputListeners,
            resultListProps,
            resultListListeners,
            results,
            resultProps,
          }"
        >
          <div v-bind="rootProps">
            <input
              type="search"
              v-bind="inputProps"
              v-on="inputListeners"
              :class="[
                'form-control',
                'autocomplete-input',
                {
                  'autocomplete-input-no-results': paginatorInfo.total === 0,
                },
              ]"
              @focus="$event.target.select()"
              @blur="checkEmpty()"
            />
            <ul
              v-if="paginatorInfo.total === 0"
              class="modelarium-autocomplete__result-list"
              style="position: absolute; z-index: 1; width: 100%; top: 100%"
            >
              <li class="modelarium-autocomplete__total-results">
                {{ messages["No results found"] }}
              </li>
            </ul>
            <ul
              v-bind="resultListProps"
              v-on="resultListListeners"
              class="modelarium-autocomplete__result-list"
            >
              <li class="modelarium-autocomplete__total-results">
                {{ messages["Total results"] }}
                {{ paginatorInfo.total }}
              </li>
              <li
                v-for="(result, index) in results"
                :key="resultProps[index].id"
                v-bind="resultProps[index]"
              >
                <span>{{ result[titleField] }}</span>
              </li>
            </ul>
          </div>
        </template>
      </autocomplete>
      <router-link
        v-if="canCreate"
        :to="'/' + targetType + '/edit/'"
        target="_blank"
        title="Add a new value for this field"
        class="modelarium-autocomplete__button"
      >
        <span>＋ {{ messages["Add new"] }}</span>
      </router-link>
      <div class="modelarium-autocomplete__selection" v-if="isMultiple">
        <ul class="modelarium-autocomplete__list" tabindex="-1" title="">
          <li
            v-for="item in selectionVisible"
            :key="item.id"
            class="modelarium-autocomplete__item--selection"
            @click="removeItem(item)"
          >
            <slot v-bind:item="item">
              <span>{{ item[titleField] }}</span>
            </slot>
          </li>
        </ul>
        <button
          type="button"
          class="modelarium-autocomplete__all"
          @click="removeAll"
        >
          {{ messages["Remove all"] }}
        </button>
      </div>
    </div>
    <p class="modelarium-autocomplete__message modelarium__message">
      {{ errorMessage }}
    </p>
  </div>
</template>

<script>
import Autocomplete from "@trevoreyre/autocomplete-vue/dist/autocomplete.esm";

export default {
  components: {
    Autocomplete,
  },

  data() {
    return {
      /**
       * Actual values
       */
      actualValues: undefined,

      /**
       * This is the initial value, copied from the prop.
       */
      initialValue: undefined,

      /**
       * If some error happened.
       */
      errorMessage: "",
      /**
       * Results from the server
       */
      selectable: [],
      /**
       * What the user is typing
       */
      selectableQuery: "",
      /**
       * Pagination about returned results
       */
      paginatorInfo: {
        currentPage: 0,
        lastPage: 0,
        perPage: 0,
        total: -1, // -1: nothing yet
      },
    };
  },

  props: {
    value: {
      type: [String, Number, Array, Object],
      default: undefined,
    },

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
      default: "",
    },

    debounceTime: {
      type: Number,
      default: 200, // in milliseconds
    },

    /**
     * The field in the relationship model that is used as a title
     */
    titleField: {
      type: String,
      required: true,
    },

    /**
     * The field in the relationship model that is used to query
     */
    queryField: {
      type: String,
      required: true,
    },

    /**
     * The field in the relationship model that is used to query.
     * Set as undefined to return the entire entry object.
     */
    valueField: {
      type: String,
      default: "id",
    },

    /**
     * The target type, such as 'post'
     */
    targetType: {
      type: String,
      required: true,
    },

    /**
     * The target type plural, such as 'posts'
     */
    targetTypePlural: {
      type: String,
      required: true,
    },

    /**
     * The GraphQL query
     */
    query: {
      type: String,
      required: true,
    },

    /**
     * Is this field required?
     */
    required: {
      type: Boolean,
      default: false,
    },

    /**
     * If true accepts multiple values.
     */
    isMultiple: {
      type: Boolean,
      default: false,
    },

    /**
     * Placeholder message
     */
    placeholder: {
      type: String,
      default: "search...",
    },

    /**
     * Translatable messages
     */
    messages: {
      type: Object,
      default: () => ({
        "No results found": "No results found",
        "Total results": "Total results",
        "Remove all": "Remove all",
        "Add new": "Add new",
      }),
    },

    canCreate: {
      // TODO
      type: Boolean,
      default: false,
    },
  },

  created() {
    this.actualValues = this.isMultiple ? [] : undefined;
    if (this.value) {
      this.initialValue = this.value;
    }
  },

  computed: {
    selectionVisible() {
      if (!this.selectionQuery) {
        return this.actualValues;
      }
      return this.actualValues.filter(
        (i) => i[this.titleField].indexOf(this.selectionQuery) != -1
      );
    },
  },

  methods: {
    saveSelectionAndReset(e) {
      let val = e.target.value;
      if (val) {
        this.optionVal = val;
      }
      e.target.value = "";
    },

    /**
     * handles when field is cleared.
     */
    checkEmpty() {
      if (this.isMultiple) {
        // TODO
      } else {
        if (this.selectableQuery) {
          return;
        }
        this.actualValues = undefined;
        this.$emit("input", this.actualValues);
      }
    },

    /**
     * push submit events.
     */
    onSubmit(result) {
      const v = this.valueField ? result[this.valueField] : result;

      if (this.isMultiple) {
        this.actualValues.push(v);
      } else {
        if (result) {
          this.$set(this, "actualValues", v);
        } else {
          this.$set(this, "actualValues", undefined);
        }
      }
      this.$emit("input", this.actualValues);
    },

    autocompleteSearch(input) {
      this.selectableQuery = input;
      return this.fetch();
    },

    autocompleteGetResultValue(result) {
      return result[this.titleField];
    },

    async fetch() {
      this.isLoading = true;
      return axios
        .post("/graphql", {
          query: this.query,
          variables: {
            page: 1,
            [this.queryField]: this.selectableQuery,
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
          const results = data[this.targetTypePlural].data;
          const paginatorInfo = data[this.targetTypePlural].paginatorInfo;
          this.$set(this, "selectable", results);
          this.$set(this, "paginatorInfo", paginatorInfo);
          return results;
        })
        .finally(() => {
          this.isLoading = false;
        });
    },

    addItem(item) {
      this.actualValues.push(item);
    },

    removeItem(item) {
      this.actualValues = this.actualValues.filter(
        (value) => item.id != value.id
      );
    },

    removeAll(item) {
      this.$set(this, "value", []);
    },
  },
};
</script>

<style scoped>
/* Loading spinner */
.autocomplete[data-loading="true"]::after {
  content: "";
  border: 3px solid rgba(0, 0, 0, 0.12);
  border-right: 3px solid rgba(0, 0, 0, 0.48);
  border-radius: 100%;
  width: 20px;
  height: 20px;
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  animation: rotate 1s infinite linear;
}

.modelarium-autocomplete__result-list {
  margin: 0;
  border: 1px solid rgba(0, 0, 0, 0.12);
  padding: 0;
  box-sizing: border-box;
  max-height: 296px;
  overflow-y: auto;
  background: #fff;
  list-style: none;
  box-shadow: 0 2px 2px rgba(0, 0, 0, 0.16);
}

[data-position="below"] .modelarium-autocomplete__result-list {
  margin-top: -1px;
  border-top-color: transparent;
  border-radius: 0 0 8px 8px;
  padding-bottom: 8px;
}

[data-position="above"] .modelarium-autocomplete__result-list {
  margin-bottom: -1px;
  border-bottom-color: transparent;
  border-radius: 8px 8px 0 0;
  padding-top: 8px;
}

.modelarium-autocomplete__total-results {
  padding: 0px 12px;
  font-size: 0.8rem;
  font-style: italic;
}

/* Single result item */
.autocomplete-result {
  cursor: default;
  padding: 12px 12px 12px 12px;
}

.autocomplete-result:hover,
.autocomplete-result[aria-selected="true"] {
  background-color: rgba(0, 0, 0, 0.06);
}

@keyframes rotate {
  from {
    transform: translateY(-50%) rotate(0deg);
  }
  to {
    transform: translateY(-50%) rotate(359deg);
  }
}
</style>
